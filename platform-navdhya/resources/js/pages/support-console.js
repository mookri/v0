// Support staff console (plan slice 4, doc 06 §7). Queue and thread
// READS go through the API on the session cookie (SessionContextForReads
// keeps cookie writes off the api group); replies and closes POST to the
// SHARED web actions — cookie sessions never authenticate API writes.

import {
  apiGet,
  apiMeta,
  appendRow,
  badge,
  fillWorkspaceBadge,
  fillWorkspaceFields,
  setStateRow,
  showError,
} from '../lib/admin-api.js';

const rowsEl = document.getElementById('support-rows');
const alertEl = document.getElementById('support-alert');
const moreBtn = document.getElementById('support-load-more');
const threadCard = document.getElementById('support-thread-card');
const threadTitle = document.getElementById('support-thread-title');
const threadStatus = document.getElementById('support-thread-status');
const messagesEl = document.getElementById('support-messages');
const replyForm = document.getElementById('support-reply-form');
const closeForm = document.getElementById('support-close-form');

const COLS = 6;
const PAGE = 25;

let nextCursor = null;
let loading = false;

function statusBadge(status) {
  if (status === 'OPEN') return badge('OPEN', 'bg-warning-subtle text-warning');
  if (status === 'CLOSED') return badge('CLOSED', 'bg-secondary-subtle text-secondary');
  return badge(status || '—');
}

function customerText(customer) {
  if (!customer) return 'Unknown customer';
  const who = customer.name || 'Customer';
  return customer.email ? `${who} (${customer.email})` : who;
}

function formatDate(value) {
  if (!value) return '';
  const at = new Date(value);
  return Number.isNaN(at.getTime()) ? String(value) : at.toLocaleString();
}

function appendItems(items) {
  for (const item of items) {
    const tr = appendRow(rowsEl, [
      item.subject || '',
      item.category || '',
      customerText(item.customer),
      statusBadge(item.status),
      { text: formatDate(item.created_at), cls: 'text-nowrap' },
      '',
    ]);

    // Opening a ticket is a READ — a plain button, never a mutation form.
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-outline-primary btn-sm';
    button.textContent = 'Open';
    button.addEventListener('click', () => selectTicket(item.id));
    tr.lastElementChild.appendChild(button);
  }
}

async function loadQueue({ cursor = '' } = {}) {
  if (loading) return;
  loading = true;
  alertEl.classList.add('d-none');

  if (cursor === '') {
    rowsEl.innerHTML = '';
    nextCursor = null;
  }
  if (rowsEl.childElementCount === 0) {
    setStateRow(rowsEl, 'Loading tickets…', COLS);
  }

  try {
    const body = await apiGet('/customers/support/staff/tickets', { limit: PAGE, cursor });
    const items = Array.isArray(body.data) ? body.data : [];

    rowsEl.innerHTML = '';
    if (items.length === 0) {
      setStateRow(rowsEl, 'No support tickets yet.', COLS);
    } else {
      appendItems(items);
    }

    nextCursor = (body.meta && body.meta.page && body.meta.page.next_cursor) || null;
    moreBtn.classList.toggle('d-none', !nextCursor);
  } catch (error) {
    showError(alertEl, error);
    if (rowsEl.childElementCount === 0) {
      setStateRow(rowsEl, 'Unable to load the queue.', COLS);
    }
  } finally {
    loading = false;
  }
}

function renderThread(ticket) {
  threadTitle.textContent = ticket.subject || 'Ticket';

  const open = ticket.status === 'OPEN';
  threadStatus.className = open
    ? 'badge bg-warning-subtle text-warning'
    : 'badge bg-secondary-subtle text-secondary';
  threadStatus.textContent = ticket.status || '';

  messagesEl.innerHTML = '';
  const messages = Array.isArray(ticket.messages) ? ticket.messages : [];
  if (messages.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'list-group-item text-muted';
    empty.textContent = 'No messages yet.';
    messagesEl.appendChild(empty);
  }

  for (const message of messages) {
    const row = document.createElement('div');
    row.className = 'list-group-item';

    const head = document.createElement('div');
    head.className = 'd-flex justify-content-between align-items-center';

    const who = document.createElement('strong');
    const author = message.author || {};
    who.textContent = author.name || 'Unknown';

    const meta = document.createElement('div');
    const isStaff = message.by === 'staff';
    const role = document.createElement('span');
    role.className = isStaff ? 'badge bg-primary-subtle text-primary' : 'badge bg-info-subtle text-info';
    role.textContent = isStaff ? 'Staff' : 'Customer';
    const when = document.createElement('small');
    when.className = 'text-muted ms-2';
    when.textContent = formatDate(message.at);
    meta.appendChild(role);
    meta.appendChild(when);

    head.appendChild(who);
    head.appendChild(meta);

    const bodyEl = document.createElement('div');
    bodyEl.className = 'mt-1';
    bodyEl.textContent = message.body || '';

    row.appendChild(head);
    row.appendChild(bodyEl);
    messagesEl.appendChild(row);
  }

  // Point the shared web actions at the selected ticket (doc 06 §7.1-7.2).
  replyForm.action = `/customers/support/tickets/${ticket.id}/messages`;
  closeForm.action = `/customers/support/tickets/${ticket.id}:close`;
  closeForm.classList.toggle('d-none', !open);

  threadCard.classList.remove('d-none');
}

async function selectTicket(id) {
  alertEl.classList.add('d-none');

  try {
    const body = await apiGet(`/customers/support/staff/tickets/${id}`);
    renderThread(body.data || {});
  } catch (error) {
    showError(alertEl, error);
  }
}

function main() {
  fillWorkspaceBadge(document.getElementById('support-workspace'));
  fillWorkspaceFields();

  closeForm.addEventListener('submit', (event) => {
    if (!window.confirm('Close this ticket?')) event.preventDefault();
  });

  moreBtn.addEventListener('click', () => loadQueue({ cursor: nextCursor || '' }));

  if (!apiMeta('api-tenant')) {
    setStateRow(rowsEl, 'Choose a workspace to load the queue.', COLS);
    return;
  }

  loadQueue();
}

main();
