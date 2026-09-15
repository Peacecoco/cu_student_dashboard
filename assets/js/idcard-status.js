(() => {
  const apiUrl = new URL('../index.php', window.location.href).toString();
  const input = document.getElementById('identifier');
  const button = document.getElementById('searchButton');
  const message = document.getElementById('statusMessage');
  const results = document.getElementById('applicationResults');
  const queryIdentifier = new URLSearchParams(window.location.search).get('identifier');
  const request = async url => { const response = await fetch(url); const body = await response.json().catch(() => ({})); if (!response.ok || !body.success) throw new Error(body.message || 'Unable to retrieve application status.'); return body; };
  const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' })[character]);
  const label = status => ({ submitted: 'Submitted', awaitingpayment: 'Awaiting payment', paid: 'Paid — awaiting printing', printed: 'Printed', readyforpickup: 'Ready for pickup', acknowledged: 'Collected', rejected: 'Rejected', cancelled: 'Cancelled', closed: 'Closed', expired: 'Payment expired' }[String(status).toLowerCase()] || status);
  const date = value => value ? new Date(value.replace(' ', 'T')).toLocaleDateString() : 'Not available';
  const money = value => Number(value || 0).toLocaleString('en-NG', { style: 'currency', currency: 'NGN' });
  const show = (text, type) => { message.textContent = text; message.className = `notice show ${type}`; };
  const render = applications => {
    results.innerHTML = applications.map(app => {
      const status = String(app.status).toLowerCase();
      const canPay = status === 'awaitingpayment';
      const nextStep = canPay ? 'Your application has been approved. Complete payment before the deadline so it can enter the printing queue.' : status === 'paid' ? 'Payment is confirmed. Your card is awaiting printing.' : status === 'readyforpickup' ? 'Your replacement card is ready for collection.' : status === 'submitted' ? 'Your request is awaiting review by Student Services.' : 'See the application status above for the latest update.';
      return `<article class="application-card"><div class="card-head"><div><strong>${escapeHtml(app.referencenumber)}</strong><span>${escapeHtml(app.applicationtype === 'loststolen' ? 'Lost / Stolen' : 'Damaged')}</span></div><span class="status-badge status-${escapeHtml(status)}">${escapeHtml(label(status))}</span></div><p>${escapeHtml(nextStep)}</p><div class="meta-grid"><div><small>Submitted</small><b>${date(app.createdat)}</b></div><div><small>Approved fee</small><b>${app.approvedfee ? money(app.approvedfee) : 'Pending review'}</b></div><div><small>Payment deadline</small><b>${date(app.paymentdeadline)}</b></div></div>${canPay ? `<a class="btn primary pay-link" href="paymentcenter.php?ref=${encodeURIComponent(app.referencenumber)}">Make payment</a>` : ''}</article>`;
    }).join('');
    results.hidden = false;
  };
  const load = async () => {
    const identifier = input.value.trim();
    if (!identifier) return show('Enter your matriculation number.', 'error');
    button.disabled = true; button.textContent = 'Checking...'; results.hidden = true;
    try { const response = await request(`${apiUrl}?identifier=${encodeURIComponent(identifier)}`); render(response.data.applications || []); show(`${response.data.applications.length} application(s) found.`, 'success'); }
    catch (error) { show(error.message, 'error'); }
    finally { button.disabled = false; button.textContent = 'Check status'; }
  };
  button.addEventListener('click', load);
  input.addEventListener('keydown', event => { if (event.key === 'Enter') load(); });
  if (queryIdentifier) { input.value = queryIdentifier; load(); }
})();
