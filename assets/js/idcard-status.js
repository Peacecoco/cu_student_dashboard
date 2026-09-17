(async () => {
  const U=window.IDCardUI,rows=document.getElementById('applicationResults'),message=document.getElementById('statusMessage');
  async function load(){
    const apps=await U.get('applications');
    rows.innerHTML=apps.length?apps.map(a=>`<tr><td><strong>${U.esc(a.referencenumber)}</strong><small>${a.applicationtype==='damaged'?'Damaged / Faded':'Lost / Stolen'}</small></td><td><button class="table-action" data-payment="${U.esc(a.referencenumber)}">${a.paymentstatus?U.label(a.paymentstatus):'Not available (legacy)'}</button></td><td><button class="table-action" data-history="${U.esc(a.referencenumber)}">View history</button></td><td><span class="status-badge">${U.label(a.status)}</span>${a.refundstatus?`<small>Refund ${U.label(a.refundstatus).toLowerCase()}</small>`:''}</td></tr>`).join(''):'<tr><td colspan="4">You have no replacement applications yet.</td></tr>';
  }
  try{await U.ready;await load();document.getElementById('openRefund').disabled=false;}
  catch(e){rows.innerHTML='<tr><td colspan="4">Your requests could not be loaded.</td></tr>';U.show(message,e.message);return;}
  let detailRequest=0;
  rows.addEventListener('click',async event=>{
    const trigger=event.target.closest('[data-payment],[data-history]');if(!trigger)return;
    const serial=++detailRequest;const payment=trigger.hasAttribute('data-payment'),ref=payment?trigger.dataset.payment:trigger.dataset.history;
    const dialog=document.getElementById(payment?'paymentDialog':'historyDialog'),content=document.getElementById(payment?'paymentDetails':'historyDetails');
    content.textContent='Loading...';if(!payment)document.getElementById('historyReference').textContent=ref;dialog.showModal();
    try{
      const data=await U.get(payment?'paymenthistory':'history',{ref});if(serial!==detailRequest)return;
      if(payment){
        content.innerHTML=U.fields([['Reference ID',data.application.referencenumber],['Payment status',data.application.paymentstatus?U.label(data.application.paymentstatus):'Not available for this legacy application']])+(data.transactions.length?data.transactions.map(t=>U.fields([['Recorded result',U.label(t.status)],['Replacement fee',U.money(t.baseamount,t.currency)],['Charges',U.money(t.chargeamount,t.currency)],['Total amount',U.money(t.totalamount,t.currency)],['Currency',t.currency],['Provider',t.provider==='local-simulator'?'Local simulator (no money charged)':t.provider],['Payment reference',t.paymentreference],['Gateway reference',t.gatewayreference],['Payment date / time',U.date(t.paidat)],['Completion date / time',U.date(t.completedat)],['Record created',U.date(t.createdat)],['Failure information',t.failuremessage]])).join(''):'<p>No payment transaction information is available.</p>');
      }else{
        const labels={payment_paid:'Payment successful (local simulation)',payment_failed:'Payment failed (local simulation)',refund_requested:'Refund requested',refund_approved:'Refund approved by Student Affairs',refund_credited:'Refund credited by Account Office',card_printed:'ID card printed',card_collected:'ID card collected'};
        content.innerHTML=data.length?`<ol class="timeline">${data.map(e=>`<li><strong>${U.esc(labels[e.eventtype] || 'Application updated')}</strong><time>${U.esc(U.date(e.occurredat))}</time></li>`).join('')}</ol>`:'<p>Detailed history is not available for this legacy application.</p>';
      }
    }catch(e){content.textContent=e.message;}
  });
  const dialog=document.getElementById('refundDialog'),form=document.getElementById('refundForm'),button=document.getElementById('submitRefund'),notice=document.getElementById('refundMessage');
  document.getElementById('openRefund').addEventListener('click',()=>{form.reset();notice.className='notice';dialog.showModal();});
  form.addEventListener('submit',async event=>{
    event.preventDefault();if(button.disabled)return;button.disabled=true;
    try{const result=await U.post('requestrefund',{referencenumber:document.getElementById('refundReference').value.trim()});dialog.close();U.show(message,result.message,'success');await load();}
    catch(e){U.show(dialog.open?notice:message,e.message);}finally{button.disabled=false;}
  });
})();
