(() => {
  const apiUrl = new URL('../index.php', window.location.href).toString();
  const gateInput = document.getElementById('gateApplicantIdentifier');
  const gateButton = document.getElementById('gateCheckButton');
  const formPanel = document.getElementById('applicationFormContainer');
  const eligibility = document.getElementById('eligibilityCard');
  const form = document.getElementById('applyForm');
  const identifier = document.getElementById('applicantIdentifier');
  const applicationType = document.getElementById('applicationType');
  const documentType = document.getElementById('documentType');
  const documentFile = document.getElementById('supportingDocument');
  const photoFile = document.getElementById('passportPhoto');
  const photoPreview = document.getElementById('photoPreview');
  const photoPreviewContainer = document.getElementById('photoPreviewContainer');
  const settingsNotice = document.getElementById('settingsNotice');
  const statusMessage = document.getElementById('statusMessage');
  const submitButton = document.getElementById('submitButton');
  const modal = document.getElementById('successModal');
  const successMessage = document.getElementById('successMessage');
  const reasonConfig = { loststolen: 'Police Report or Affidavit', damaged: 'Damaged Card Evidence' };
  const activeStatuses = ['submitted', 'awaitingpayment', 'paid', 'printed', 'readyforpickup', 'acknowledged'];

  const show = (element, message, type) => { element.textContent = message; element.className = `notice show ${type}`; };
  const clear = (element) => { element.textContent = ''; element.className = 'notice'; };
  const request = async (url, options = {}) => { const response = await fetch(url, options); const result = await response.json().catch(() => ({})); if (!response.ok || !result.success) throw new Error(result.message || 'Unable to complete the request.'); return result; };

  gateButton.addEventListener('click', async () => {
    const value = gateInput.value.trim();
    if (!value) return show(eligibility, 'Enter your matriculation number.', 'error');
    gateButton.disabled = true; gateButton.textContent = 'Checking...'; clear(eligibility);
    try {
      const result = await request(`${apiUrl}?identifier=${encodeURIComponent(value)}`);
      const applications = result.data.applications || [];
      const active = applications.find(app => activeStatuses.includes(app.status));
      if (active) { formPanel.hidden = true; return show(eligibility, `You already have an active application: ${active.referencenumber}.`, 'warning'); }
      identifier.value = value; formPanel.hidden = false; show(eligibility, 'You can continue with a new application.', 'success');
    } catch (error) {
      if (/no applications found/i.test(error.message)) { identifier.value = value; formPanel.hidden = false; show(eligibility, 'You can continue with a new application.', 'success'); }
      else { formPanel.hidden = true; show(eligibility, error.message, 'error'); }
    } finally { gateButton.disabled = false; gateButton.textContent = 'Continue'; }
  });

  applicationType.addEventListener('change', async () => {
    documentType.value = reasonConfig[applicationType.value] || '';
    const enabled = Boolean(applicationType.value); documentFile.disabled = !enabled; photoFile.disabled = !enabled;
    if (!enabled) return;
    try { const result = await request(`${apiUrl}?action=settings&applicationtype=${encodeURIComponent(applicationType.value)}`); const setting = result.data[0]; settingsNotice.textContent = `Payment deadline after approval: ${setting.expirydays} day(s). Maximum upload size: ${(setting.maxfilesizebytes / 1048576).toFixed(2)}MB.`; }
    catch (error) { settingsNotice.textContent = error.message; }
  });

  photoFile.addEventListener('change', () => { const file = photoFile.files[0]; if (!file) return; photoPreview.src = URL.createObjectURL(file); photoPreviewContainer.hidden = false; });
  form.addEventListener('reset', () => { setTimeout(() => { documentFile.disabled = true; photoFile.disabled = true; photoPreviewContainer.hidden = true; clear(statusMessage); }, 0); });
  form.addEventListener('submit', async event => {
    event.preventDefault(); clear(statusMessage);
    if (!applicationType.value || !documentFile.files[0] || !photoFile.files[0]) return show(statusMessage, 'Complete all required fields and uploads.', 'error');
    const data = new FormData(); data.append('matricnumber', identifier.value); data.append('applicationtype', applicationType.value); data.append('documenttype', documentType.value); data.append('document', documentFile.files[0]); data.append('photo', photoFile.files[0]);
    submitButton.disabled = true; submitButton.textContent = 'Submitting...';
    try { const result = await request(`${apiUrl}?action=submit`, { method: 'POST', body: data }); successMessage.textContent = `Your application was submitted successfully. Reference number: ${result.data.referencenumber}.`; modal.hidden = false; }
    catch (error) { show(statusMessage, error.message, 'error'); }
    finally { submitButton.disabled = false; submitButton.textContent = 'Submit application'; }
  });
  document.getElementById('closeSuccessModal').addEventListener('click', () => { window.location.href = `checkappstatus.php?identifier=${encodeURIComponent(identifier.value)}`; });
})();
