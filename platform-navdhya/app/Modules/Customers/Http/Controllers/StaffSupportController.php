<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Models\User;
use App\Modules\Customers\Models\SupportMessage;
use App\Modules\Customers\Models\SupportTicket;
use App\Platform\Api\CursorPagination;
use App\Platform\Api\WebMutations;
use App\Platform\Audit\AuditWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Support tickets, staff side (plan slice 4, doc 06 §7): the tenant-wide
 * queue over the customers module's rows — list, thread with authorship,
 * replies (re-open CLOSED tickets exactly like customer replies) and
 * closes. Capability-gated (customers:support:view/manage) behind the
 * tenant gate, and reachable BOTH from /api/v1 (bearer) and the console's
 * shared web actions (session + CSRF, negotiated responses). Machine
 * principals stay denied through the self-serve base's guard — support
 * threads are customer data, human-only. Staff actions write audit rows
 * only: the customer activity feed records the customer's own actions,
 * never someone else's.
 */
final class StaffSupportController extends CustomerSelfServiceController
{
    public function __construct(
        private readonly AuditWriter $audit,
    ) {
    }

    /**
     * Tenant-wide queue (newest first, cursor-paginated) with the filing
     * customer masked the way every other surface masks contact points.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $validated = $request->validate([
            'status' => ['sometimes', 'nullable', 'string', 'in:OPEN,CLOSED'],
        ]);

        $query = SupportTicket::query();
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        [$tickets, $page] = CursorPagination::paginate($query, $request);

        $customers = $this->findUsers(array_values(array_unique(array_map(
            static fn (SupportTicket $ticket): string => (string) $ticket->user_id,
            $tickets,
        ))));

        return new JsonResponse([
            'data' => array_map(
                fn (SupportTicket $ticket): array => $this->presentTicket(
                    $ticket,
                    $customers->get((string) $ticket->user_id),
                ),
                $tickets,
            ),
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }

    /**
     * One ticket with its full thread; every message carries its author
     * and whether it came from the customer or the desk.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $ticket = $this->ticket($request, $id);
        if ($ticket instanceof JsonResponse) {
            return $ticket;
        }

        $messages = $this->messages($ticket);
        $authors = $this->findUsers(array_merge(
            [(string) $ticket->user_id],
            array_map(static fn (SupportMessage $message): string => (string) $message->user_id, $messages),
        ));

        return new JsonResponse([
            'data' => [
                ...$this->presentTicket($ticket, $authors->get((string) $ticket->user_id)),
                'messages' => array_map(
                    fn (SupportMessage $message): array => $this->presentMessage($message, $ticket, $authors),
                    $messages,
                ),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * Staff reply: same reopen-on-message semantics as the customer's own
     * reply (silence after resolution is a follow-up, not a void write).
     * Shared web action — the console's reply form posts here (doc 06
     * §7.1-7.2), the API answers its envelope untouched.
     */
    public function reply(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return WebMutations::negotiate($request, $user, '');
        }
        $staffId = (string) $user->getAuthIdentifier();

        $ticket = $this->ticket($request, $id);
        if ($ticket instanceof JsonResponse) {
            return WebMutations::negotiate($request, $ticket, '');
        }

        $validated = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $reopened = $ticket->status === SupportTicket::STATUS_CLOSED;
        if ($reopened) {
            $ticket->forceFill(['status' => SupportTicket::STATUS_OPEN])->save();
        }

        $message = $this->writeMessage($ticket, $staffId, $validated['body']);

        $this->audit->record(
            'customers.ticket.replied',
            ['module' => 'customers', 'resource_type' => 'support_ticket', 'resource_id' => (string) $ticket->getKey()],
            null,
            ['user_id' => $staffId, 'via' => 'staff', 'reopened' => $reopened],
        );

        $authors = $this->findUsers([(string) $ticket->user_id, $staffId]);

        return WebMutations::negotiate(
            $request,
            new JsonResponse([
                'data' => [
                    ...$this->presentTicket($ticket, $authors->get((string) $ticket->user_id)),
                    'messages' => [$this->presentMessage($message, $ticket, $authors)],
                ],
                'meta' => ['request_id' => $this->requestId($request)],
            ], 201),
            'Reply sent.',
        );
    }

    /**
     * Staff close (already-closed is 409 state_forbidden — identical code
     * to the customer side). Shared web action, same negotiation.
     */
    public function close(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return WebMutations::negotiate($request, $user, '');
        }
        $staffId = (string) $user->getAuthIdentifier();

        $ticket = $this->ticket($request, $id);
        if ($ticket instanceof JsonResponse) {
            return WebMutations::negotiate($request, $ticket, '');
        }

        if ($ticket->status === SupportTicket::STATUS_CLOSED) {
            return WebMutations::negotiate($request, new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Ticket is already closed.', 'request_id' => $this->requestId($request)],
            ], 409), '');
        }

        $ticket->forceFill(['status' => SupportTicket::STATUS_CLOSED])->save();

        $this->audit->record(
            'customers.ticket.closed',
            ['module' => 'customers', 'resource_type' => 'support_ticket', 'resource_id' => (string) $ticket->getKey()],
            null,
            ['user_id' => $staffId, 'via' => 'staff'],
        );

        $customer = $this->findUsers([(string) $ticket->user_id])->get((string) $ticket->user_id);

        return WebMutations::negotiate(
            $request,
            new JsonResponse([
                'data' => $this->presentTicket($ticket, $customer),
                'meta' => ['request_id' => $this->requestId($request)],
            ]),
            'Ticket closed.',
        );
    }

    /**
     * TenantScope binds the read to the resolved tenant — foreign ids
     * miss the row and collapse into the same 404 as unknown ids.
     */
    private function ticket(Request $request, string $id): SupportTicket|JsonResponse
    {
        $ticket = SupportTicket::query()->where('id', $id)->first();

        if ($ticket === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Ticket not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return $ticket;
    }

    /**
     * @return list<SupportMessage>
     */
    private function messages(SupportTicket $ticket): array
    {
        return SupportMessage::query()
            ->where('ticket_id', (string) $ticket->getKey())
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->all();
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
     * Batch-resolve identities in one query — never N+1. Deleted
     * accounts fall out of the default scope; surfaces show them as
     * absent rather than resurrecting the row.
     *
     * @param  list<string>  $ids
     * @return Collection<string, User>
     */
    private function findUsers(array $ids): Collection
    {
        $ids = array_values(array_unique(array_filter($ids)));

        if ($ids === []) {
            return new Collection();
        }

        return User::query()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy(static fn (User $user): string => (string) $user->getKey());
    }

    /**
     * @return array<string, mixed>
     */
    private function presentTicket(SupportTicket $ticket, ?User $customer): array
    {
        return [
            'id' => (string) $ticket->getKey(),
            'object' => 'support_ticket',
            'subject' => $ticket->subject,
            'category' => $ticket->category,
            'status' => $ticket->status,
            'customer' => $customer === null ? null : [
                'id' => (string) $customer->getKey(),
                'name' => $customer->name,
                'email' => self::maskEmail($customer->email),
            ],
            'created_at' => $ticket->created_at,
            'updated_at' => $ticket->updated_at,
        ];
    }

    /**
     * @param  Collection<string, User>  $authors
     * @return array<string, mixed>
     */
    private function presentMessage(SupportMessage $message, SupportTicket $ticket, Collection $authors): array
    {
        // Authorship derives from ids: the ticket's filer is the customer
        // side, everyone else in the thread is the desk (no schema change
        // needed — the migration anticipated staff ids "the same way").
        $author = $authors->get((string) $message->user_id);

        return [
            'id' => (string) $message->getKey(),
            'body' => $message->body,
            'at' => $message->created_at,
            'by' => ((int) $message->user_id === (int) $ticket->user_id) ? 'customer' : 'staff',
            'author' => [
                'id' => (string) $message->user_id,
                'name' => $author->name ?? 'Removed user',
            ],
        ];
    }
}
