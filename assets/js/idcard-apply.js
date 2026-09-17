(async () => {
  const U=window.IDCardUI, gate=document.getElementById('gateForm'), panel=document.getElementById('applicationFormContainer'), notice=document.getElementById('eligibilityCard'), form=document.getElementById('applyForm'), reason=document.getElementById('applicationType'), photo=document.getElementById('passportPhoto'), resume=document.getElementById('resumeCheckout');
  let settings=[], previewUrl=null, student=null;
  if (new URLSearchParams(location.search).get('cancelled')==='1') U.show(notice,'Checkout cancelled. No replacement application was created.','info');
  try { student=await U.ready; settings=await U.get('settings'); document.getElementById('gateCheckButton').disabled=false; }
  catch (e) { U.show(notice,e.message); return; }
  gate.addEventListener('submit',async event=>{
    event.preventDefault(); const button=document.getElementById('gateCheckButton');button.disabled=true;panel.hidden=true;resume.hidden=true;
    try {
      const data=await U.get('eligibility',{matricnumber:student.student.matricnumber});
      if (data.pending) { resume.href=`paymentcenter.php?attempt=${encodeURIComponent(data.pending)}`;resume.hidden=false;U.show(notice,'You have a pending checkout. Continue it or cancel it before changing replacement details.','info'); }
      else { document.getElementById('applicantIdentifier').value=data.student.matricnumber;panel.hidden=false;U.show(notice,'You can continue with your replacement details.','success'); }
    } catch(e) { U.show(notice,e.message); } finally { button.disabled=false; }
  });
  reason.addEventListener('change',()=>{
    const setting=settings.find(s=>s.applicationtype===reason.value);
    document.getElementById('settingsNotice').textContent=setting ? `Replacement fee: ${U.money(setting.approvedfee,'NGN')}. Upload a JPEG or PNG passport photo, up to ${(setting.maxfilesizebytes/1048576).toFixed(2)} MB. Any configured payment charges are shown at checkout.` : 'Select a reason to view the replacement fee and photo requirements.';
  });
  photo.addEventListener('change',()=>{
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    document.getElementById('photoPreviewContainer').hidden=!photo.files.length;
    if (photo.files[0]) { previewUrl=URL.createObjectURL(photo.files[0]);document.getElementById('photoPreview').src=previewUrl; }
  });
  form.addEventListener('reset',()=>{ if(previewUrl) URL.revokeObjectURL(previewUrl);document.getElementById('photoPreviewContainer').hidden=true; });
  form.addEventListener('submit',async event=>{
    event.preventDefault();const button=document.getElementById('submitButton');if(button.disabled)return;
    button.disabled=true;button.textContent='Preparing checkout...';
    try {
      const body=new FormData();body.append('matricnumber',document.getElementById('applicantIdentifier').value);body.append('applicationtype',reason.value);
      if(photo.files[0])body.append('photo',photo.files[0]);
      const data=await U.post('begincheckout',body);location.href=`paymentcenter.php?attempt=${encodeURIComponent(data.paymentreference)}`;
    } catch(e) {U.show(document.getElementById('statusMessage'),e.message);button.disabled=false;button.textContent='Proceed to checkout';}
  });
})();
