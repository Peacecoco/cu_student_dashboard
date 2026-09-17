(() => {
  const api = new URL('../index.php', location.href);
  const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const show = (element, text, kind = 'error') => { element.textContent = text; element.className = `notice show ${kind}`; };
  async function get(action, params = {}) {
    const url = new URL(api); url.search = new URLSearchParams({action, ...params});
    return read(await fetch(url, {credentials: 'same-origin', cache: 'no-store'}));
  }
  async function read(response) {
    const result = await response.json().catch(() => ({}));
    if (!response.ok || !result.success) throw new Error(result.message || 'Unable to complete the request. Please try again.');
    return result.data;
  }
  const ready = get('session').then(data => {
    document.getElementById('studentIdentity').textContent = `${data.student.name} (${data.student.matricnumber})`;
    return data;
  });
  async function post(action, body) {
    const session = await ready;
    const multipart = body instanceof FormData;
    return read(await fetch(`${api}?action=${encodeURIComponent(action)}`, {method: 'POST', credentials: 'same-origin', headers: {'X-CSRF-Token': session.csrf, ...(multipart ? {} : {'Content-Type':'application/json'})}, body: multipart ? body : JSON.stringify(body)}));
  }
  const labels = {paid:'Paid',failed:'Failed',printed:'Printed',collected:'Collected',refunded:'Refunded',submitted:'Submitted (legacy)',awaitingpayment:'Awaiting payment (legacy)',readyforpickup:'Ready for pickup',acknowledged:'Collected',closed:'Closed',cancelled:'Cancelled',rejected:'Rejected',expired:'Expired',successful:'Recorded successful (legacy)',requested:'Requested',approved:'Approved',credited:'Credited'};
  const label = value => labels[value] || 'Not available';
  const money = (value, currency) => value == null ? 'N/A' : currency ? new Intl.NumberFormat('en-NG',{style:'currency',currency}).format(Number(value)) : `${Number(value).toLocaleString('en-NG',{minimumFractionDigits:2})} (currency not recorded)`;
  const date = value => value ? new Date(String(value).replace(' ','T') + '+01:00').toLocaleString('en-GB',{timeZone:'Africa/Lagos',dateStyle:'medium',timeStyle:'medium'}) + ' (Lagos)' : 'N/A';
  const fields = pairs => `<dl class="details-grid">${pairs.map(([key,value])=>`<div><dt>${esc(key)}</dt><dd>${esc(value ?? 'N/A')}</dd></div>`).join('')}</dl>`;
  document.addEventListener('click', event => { if (event.target.closest('[data-close]')) event.target.closest('dialog').close(); });
  window.IDCardUI = {get,post,ready,esc,show,label,money,date,fields};
})();
