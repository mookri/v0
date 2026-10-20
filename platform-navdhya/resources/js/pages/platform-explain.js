// Explain access — Administration's diagnostic for the SEC-022 precedence
// chain (GET /api/v1/authz/explain, capability platform:authz:explain;
// read-only — the form never posts, it builds a query through apiGet with
// the session cookie + X-Tenant-Id). Member suggestions come from
// GET /platform/users when the caller also holds platform:user:view;
// otherwise a raw membership id still works, so the two capabilities stay
// independent.

import { apiGet, apiMeta, appendRow, badge, fillWorkspaceBadge, setStateRow, showError } from '../lib/admin-api.js';

const alertEl = document.getElementById('explain-alert');
const decisionEl = document.getElementById('explain-decision');
const metaEl = document.getElementById('explain-meta');
const policiesEl = document.getElementById('explain-policies');
const stageRowsEl = document.getElementById('explain-stage-rows');
const membersList = document.getElementById('explain-members');
const membersHint = document.getElementById('explain-members-hint');
const form = document.getElementById('explain-form');

const COLS = 4;
const ATTRIBUTES = ['amount', 'currency', 'org', 'time', 'status'];

function outcomeBadge(outcome) {
  if (outcome === 'allow') return badge('allow', 'bg-success-subtle text-success');
  if (outcome === 'deny') return badge('deny', 'bg-danger-subtle text-danger');
  return badge(String(outcome || '—'), 'bg-secondary-subtle text-secondary'); // 'continue'
}

// Details are engine-built rule/attribute maps — JSON text in a monospace
// cell (textContent only, never HTML).
function detailsText(details) {
  if (details === null || details === undefined) return '—';
  if (typeof details === 'string') return details || '—';
  if (typeof details === 'object' && Object.keys(details).length === 0) return '—';
  try {
    return JSON.stringify(details);
  } catch {
    return '—';
  }
}

async function loadMembers() {
  try {
    const body = await apiGet('/platform/users', { tenant_id: apiMeta('api-tenant') });
    const items = Array.isArray(body && body.data) ? body.data : [];
    membersList.innerHTML = '';

    for (const item of items) {
      const option = document.createElement('option');
      option.value = item.id;
      const user = item.user || {};
      option.label = [user.name, user.email].filter(Boolean).join(' — ') || item.id;
      membersList.appendChild(option);
    }

    membersHint.textContent = items.length > 0
      ? `${items.length} member(s) in this workspace — pick one or paste a membership id.`
      : 'No member suggestions — paste a membership id.';
  } catch {
    // Suggestions need platform:user:view; a typed membership id works
    // without it, so this is a hint, not a failure.
    membersHint.textContent = 'Member suggestions unavailable — paste a membership id.';
  }
}

function render(decision, meta) {
  if (!decision) return;

  const allowed = decision.decision === 'allow';
  decisionEl.classList.remove('d-none', 'alert-success', 'alert-danger');
  decisionEl.classList.add(allowed ? 'alert-success' : 'alert-danger');
  decisionEl.textContent = `${allowed ? 'ALLOW' : 'DENY'} — decided at ${decision.deciding_stage}`;

  metaEl.textContent = [
    decision.capability,
    decision.principal_id ? `principal ${decision.principal_id}` : null,
    `cached: ${decision.cached}`,
    `logged: ${decision.logged}`,
    meta && meta.request_id ? `request ${meta.request_id}` : null,
  ].filter(Boolean).join(' · ');

  const policies = Array.isArray(decision.evaluated_policies) ? decision.evaluated_policies : [];
  policiesEl.classList.toggle('d-none', policies.length === 0);
  policiesEl.textContent = policies.length > 0 ? `Evaluated policies: ${policies.join(', ')}` : '';

  const stages = Array.isArray(decision.stages) ? decision.stages : [];
  stageRowsEl.innerHTML = '';
  if (stages.length === 0) {
    setStateRow(stageRowsEl, 'No stages recorded.', COLS);
    return;
  }

  for (const stage of stages) {
    appendRow(stageRowsEl, [
      { text: stage.stage || '—', cls: 'font-monospace' },
      outcomeBadge(stage.outcome),
      { text: stage.reason || '—' },
      { text: detailsText(stage.details), cls: 'font-monospace small' },
    ]);
  }
}

async function run(event) {
  event.preventDefault();

  const membershipId = document.getElementById('explain-membership').value.trim();
  const capability = document.getElementById('explain-capability').value.trim();
  if (!membershipId || !capability) return;

  if (alertEl) alertEl.classList.add('d-none');
  decisionEl.classList.add('d-none');
  policiesEl.classList.add('d-none');
  policiesEl.textContent = '';
  metaEl.textContent = 'Evaluating…';
  setStateRow(stageRowsEl, 'Evaluating…', COLS);

  const params = { membership_id: membershipId, capability };
  for (const field of ATTRIBUTES) {
    const el = document.getElementById(`explain-${field}`);
    const value = el ? el.value.trim() : '';
    if (value) params[field] = value;
  }

  try {
    const body = await apiGet('/authz/explain', params);
    render(body && body.data, body && body.meta);
  } catch (error) {
    showError(alertEl, error);
    metaEl.textContent = 'No trace for that request.';
    setStateRow(stageRowsEl, 'No trace to show.', COLS);
  }
}

function main() {
  fillWorkspaceBadge(document.getElementById('explain-workspace'));
  if (form) form.addEventListener('submit', run);
  loadMembers();
}

main();
