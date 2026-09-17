(async () => {
  const U=window.IDCardUI,message=document.getElementById('paymentMessage'),actions=document.getElementById('paymentActions'),cancelDialog=document.getElementById('cancelDialog'),payDialog=document.getElementById('payDialog');
  let checkout=null,active=false,busy=false,destination='applyforidcard.php?cancelled=1';
  const toggleBusy=value=>{busy=value;document.getElementById('payButton').disabled=value;document.getElementById('cancelButton').disabled=value;};
  const render=()=>{
    active=checkout.status==='pending';document.getElementById('invoicePanel').hidden=false;actions.hidden=!active;
    document.getElementById('invoiceDetails').innerHTML=U.fields([['Checkout reference',checkout.paymentreference],['Reason',checkout.applicationtype==='damaged'?'Damaged / Faded':'Lost / Stolen'],['Replacement fee',U.money(checkout.baseamount,checkout.currency)],['Payment charges',U.money(checkout.chargeamount,checkout.currency)],['Total',U.money(checkout.totalamount,checkout.currency)],['Payment method','Local simulator']]);
    document.getElementById('checkoutStatus').textContent=active?'No application has been created for this checkout.':checkout.status==='cancelled'?'Checkout cancelled. No application was created.':`Payment ${U.label(checkout.status).toLowerCase()}. Application reference: ${checkout.referencenumber}. View Application Requests for details.`;
  };
  try {
    await U.ready;const params=new URLSearchParams(location.search);const ref=params.get('attempt') || params.get('ref') || '';
    if(ref.startsWith('IDC-')) {U.show(message,'Use Application Requests to view this application and its payment information.','info');return;}
    checkout=await U.get('checkout',ref?{ref}:{});
    if(!checkout){U.show(message,'You have no pending checkout. Start from Apply for replacement.','info');return;}
    history.replaceState(null,'',`paymentcenter.php?attempt=${encodeURIComponent(checkout.paymentreference)}`);render();
  } catch(e){U.show(message,e.message);return;}
  document.getElementById('payButton').addEventListener('click',()=>{document.getElementById('payConfirmText').textContent=`Complete a local simulated payment of ${U.money(checkout.totalamount,checkout.currency)}? A terminal result will create your application.`;payDialog.showModal();});
  document.getElementById('confirmPay').addEventListener('click',async()=>{
    if(busy)return;payDialog.close();toggleBusy(true);
    try {
      const result=await U.post('processpayment',{paymentreference:checkout.paymentreference});
      checkout.status=result.paymentstatus;checkout.referencenumber=result.referencenumber;render();
      U.show(message,`${result.paymentstatus==='paid'?'Simulated payment successful.':'Simulated payment failed.'} Your ${result.paymentstatus==='paid'?'paid':'failed-payment'} request has been recorded. Reference: ${result.referencenumber}.`,result.paymentstatus==='paid'?'success':'warning');
    } catch(e){U.show(message,e.message);} finally{toggleBusy(false);}
  });
  document.getElementById('cancelButton').addEventListener('click',()=>{destination='applyforidcard.php?cancelled=1';cancelDialog.showModal();});
  document.getElementById('confirmCancel').addEventListener('click',async()=>{
    if(busy)return;cancelDialog.close();toggleBusy(true);
    try {await U.post('cancelcheckout',{paymentreference:checkout.paymentreference});active=false;toggleBusy(false);location.href=destination;}
    catch(e){U.show(message,e.message);toggleBusy(false);}
  });
  document.addEventListener('click',event=>{
    const link=event.target.closest('a[href]');if(!link || !active)return;
    if(event.ctrlKey || event.metaKey || event.shiftKey || link.target==='_blank')return;
    event.preventDefault();if(busy){U.show(message,'Please wait for the payment request to finish.','info');return;}
    destination=link.href;cancelDialog.showModal();
  });
  window.addEventListener('beforeunload',event=>{if(active || busy){event.preventDefault();event.returnValue='';}});
})();
