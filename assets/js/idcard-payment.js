(() => {
  const apiUrl = new URL('../index.php', window.location.href).toString();
  const reference = document.getElementById('reference');
  const loadButton = document.getElementById('loadButton');
  const payButton = document.getElementById('payButton');
  const optionSelect = document.getElementById('paymentOption');
  const message = document.getElementById('paymentMessage');
  const invoicePanel = document.getElementById('invoicePanel');
  const historyPanel = document.getElementById('historyPanel');
  const invoiceDetails = document.getElementById('invoiceDetails');
  const history = document.getElementById('paymentHistory');
  let invoice = null; let options = [];
  const queryReference = new URLSearchParams(window.location.search).get('ref');
  const request = async (url, init) => { const response = await fetch(url, init); const body = await response.json().catch(() => ({})); if (!response.ok || !body.success) throw new Error(body.message || 'Unable to complete the payment request.'); return body; };
  const money = value => Number(value || 0).toLocaleString('en-NG', { style: 'currency', currency: 'NGN' });
  const date = value => value ? new Date(value.replace(' ', 'T')).toLocaleDateString() : 'Not available';
  const show = (text, type) => { message.textContent = text; message.className = `notice show ${type}`; };
  const selected = () => options.find(option => option.optioncode === optionSelect.value);
  const renderInvoice = () => { const option = selected(); invoiceDetails.innerHTML = `<div><small>Application reference</small><b>${invoice.referencenumber}</b></div><div><small>Replacement fee</small><b>${money(invoice.approvedfee)}</b></div><div><small>Payment deadline</small><b>${date(invoice.paymentdeadline)}</b></div><div><small>Total payable</small><b>${money(option?.totalamount)}</b></div>`; payButton.textContent = `Pay ${money(option?.totalamount)}`; };
  const loadHistory = async ref => { const response = await request(`${apiUrl}?action=paymenthistory&ref=${encodeURIComponent(ref)}`); const entries = response.data.transactions || []; history.innerHTML = entries.length ? `<div class="history-list">${entries.map(item => `<div><b>${item.paymentreference}</b><span>${item.paymentoptionname} · ${money(item.totalamount)} · ${date(item.paidat || item.createdat)}</span></div>`).join('')}</div>` : '<p>No payment has been recorded for this application.</p>'; historyPanel.hidden = false; };
  const loadInvoice = async () => { const ref = reference.value.trim(); if (!ref) return show('Enter an application reference.', 'error'); loadButton.disabled = true; loadButton.textContent = 'Loading...'; invoicePanel.hidden = true; try { const response = await request(`${apiUrl}?action=paymentinvoice&ref=${encodeURIComponent(ref)}`); invoice = response.data.application; options = response.data.paymentoptions || []; if (!options.length) { show(invoice.status === 'expired' ? 'This payment deadline has passed.' : 'This application does not have a pending payment invoice.', 'warning'); await loadHistory(ref); return; } optionSelect.innerHTML = options.map(option => `<option value="${option.optioncode}">${option.optionname}</option>`).join(''); renderInvoice(); invoicePanel.hidden = false; show('Invoice loaded. Select a payment gateway to continue.', 'success'); await loadHistory(ref); } catch (error) { show(error.message, 'error'); } finally { loadButton.disabled = false; loadButton.textContent = 'Load invoice'; } };
  optionSelect.addEventListener('change', renderInvoice); loadButton.addEventListener('click', loadInvoice);
  payButton.addEventListener('click', async () => { const option = selected(); if (!invoice || !option) return; if (!window.confirm(`Confirm payment of ${money(option.totalamount)} via ${option.optionname}?`)) return; payButton.disabled = true; payButton.textContent = 'Processing...'; try { const result = await request(`${apiUrl}?action=processpayment`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ referencenumber: invoice.referencenumber, paymentoptioncode: option.optioncode, gatewayreference: `${option.optioncode}-${Date.now()}` }) }); show(`Payment successful. Receipt reference: ${result.data.paymentreference}. Your card is now awaiting printing.`, 'success'); invoicePanel.hidden = true; await loadHistory(invoice.referencenumber); } catch (error) { show(error.message, 'error'); } finally { payButton.disabled = false; payButton.textContent = 'Pay'; } });
  if (queryReference) { reference.value = queryReference; loadInvoice(); }
})();
