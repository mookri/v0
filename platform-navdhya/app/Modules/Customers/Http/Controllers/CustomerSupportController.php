<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Modules\Customers\Models\SupportMessage;
use App\Modules\Customers\Models\SupportTicket;
use App\Modules\Customers\CustomerActivityRecorder;
use App\Platform\Api\CursorPagination;
use App\Platform\Audit\AuditWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Support tickets, customer side (B7): open with an initial message,
 * cursor lists, thread reads, replies (which re-open CLOSED tickets),
 * and explicit closes. All rows are caller-scoped; foreign ids 404.
 * Staff queue/assignment/SLA is a separate surface.
 */
final class CustomerSupportController extends CustomerSelfServiceController
{
    public function __construct(
        private readonly AuditWriter $audit,
        private readonly CustomerActivityRecorder $activity,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        $userId = (string) $user->getAuthIdentifier();

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:5000'],
            'category' => ['required', 'string', 'in:billing,booking,account,other'],
        ]);

        if (SupportTicket::query()->where('user_id', $userId)->where('status', SupportTicket::STATUS_OPEN)->count() >= SupportTicket::MAX_OPEN_PER_CUSTOMER) {
            return new JsonResponse([
                'error' => ['code' => 'conflict', 'message' => 'Too many open tickets.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $ticket = new SupportTicket();
        $ticket->forceFill([
            'user_id' => $userId,
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'status' => SupportTicket::STATUS_OPEN,
        ]);
        $ticket->save();

        $this->writeMessage($ticket, $userId, $validated['body']);

        $this->audit->record(
            'customers.ticket.created',
            ['module' => 'customers', 'resource_type' => 'support_ticket', 'resource_id' => (string) $ticket->getKey()],
            null,
            ['user_id' => $userId]
        );
        $this->activity->record($userId, 'support_opened', "Ticket '{$validated['subject']}' opened");

        return new JsonResponse([
            'data' => self::serialize($ticket, $this->messages($ticket)),
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        [$tickets, $page] = CursorPagination::paginate(
            SupportTicket::query()->where('user_id', $user->getAuthIdentifier()),
            $request
        );

        return new JsonResponse([
            'data' => array_map(
                static fn (SupportTicket $ticket): array => self::serialize($ticket, []),
                $tickets
            ),
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $ticket = $this->ownTicket($request, (string) $user->getAuthIdentifier(), $id);
        if ($ticket instanceof JsonResponse) {
            return $ticket;
        }

        return new JsonResponse([
            'data' => self::serialize($ticket, $this->messages($ticket)),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function reply(Request $request, string $id): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        $userId = (string) $user->getAuthIdentifier();

        $ticket = $this->ownTicket($request, $userId, $id);
        if ($ticket instanceof JsonResponse) {
            return $ticket;
        }

        $validated = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        // A reply re-opens a closed thread (documented behavior): silence
        // after resolution is a follow-up, not a void write.
        $reopened = $ticket->status === SupportTicket::STATUS_CLOSED;
        if ($reopened) {
            $ticket->forceFill(['status' => SupportTicket::STATUS_OPEN])->save();
        }

        $message = $this->writeMessage($ticket, $userId, $validated['body']);

        $this->audit->record(
            'customers.ticket.replied',
            ['module' => 'customers', 'resource_type' => 'support_ticket', 'resource_id' => (string) $ticket->getKey()],
            null,
            ['user_id' => $userId, 'reopened' => $reopened]
        );
        $this->activity->record($userId, 'support_replied', 'Replied to a support ticket');

        return new JsonResponse([
            'data' => self::serialize($ticket->fresh(), [$this->presentMessage($message)]),
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function close(Request $request, string $id): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        $userId = (string) $user->getAuthIdentifier();

        $ticket = $this->ownTicket($request, $userId, $id);
        if ($ticket instanceof JsonResponse) {
            return $ticket;
        }

        if ($ticket->status === SupportTicket::STATUS_CLOSED) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Ticket is already closed.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $ticket->forceFill(['status' => SupportTicket::STATUS_CLOSED])->save();

        $this->audit->record(
            'customers.ticket.closed',
            ['module' => 'customers', 'resource_type' => 'support_ticket', 'resource_id' => (string) $ticket->getKey()],
            null,
            ['user_id' => $userId]
        );
        $this->activity->record($userId, 'support_closed', 'Closed a support ticket');

        return new JsonResponse([
            'data' => self::serialize($ticket->fresh(), $this->messages($ticket)),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    private function ownTicket(Request $request, string $userId, string $id): SupportTicket|JsonResponse
    {
        $ticket = SupportTicket::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if ($ticket === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Ticket not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return $ticket;
    }

    private function writeMessage(SupportTicket $ticket, string $userId, string $body): SupportMessage
    {
        $message = new SupportMessage();
        $message->forceFill([
            'ticket_id' => (string) $ticket->getKey(),
            'user_id' => $userId,
            'body' => $body,
        ]);
        $message->save();

        return $message;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function messages(SupportTicket $ticket): array
    {
        return SupportMessage::query()
            ->where('ticket_id', (string) $ticket->getKey())
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (SupportMessage $message): array => $this->presentMessage($message))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function presentMessage(SupportMessage $message): array
    {
        return [
            'id' => (string) $message->getKey(),
            'body' => $message->body,
            'at' => $message->created_at,
        ];
    }

    /**
     * @param list<array<string, mixed>> $messages
     * @return array<string, mixed>
     */
    private static function serialize(SupportTicket $ticket, array $messages): array
    {
        return [
            'id' => (string) $ticket->getKey(),
            'object' => 'support_ticket',
            'subject' => $ticket->subject,
            'category' => $ticket->category,
            'status' => $ticket->status,
            'messages' => $messages,
        ];
    }
}
