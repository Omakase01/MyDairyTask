(function(){
  const THAI_MONTHS = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
  const THAI_WEEKDAYS = ['วันอาทิตย์','วันจันทร์','วันอังคาร','วันพุธ','วันพฤหัสบดี','วันศุกร์','วันเสาร์'];

  let currentUser = null;
  let viewDate = new Date();

  const $ = (id) => document.getElementById(id);

  function toast(msg){
    const t = $('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(()=> t.classList.remove('show'), 1800);
  }

  function fmtDateKey(d){
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
  }

  async function sha256(text){
    const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(text));
    return Array.from(new Uint8Array(buf)).map(b => b.toString(16).padStart(2,'0')).join('');
  }

  // ---------- Auth ----------
  // Login is handled by PHP (pages/login.php -> auth.php).
  // Do NOT intercept the login form here, otherwise the browser never sends POST
  // data to PHP and the old window.storage login system is used instead.

  const togglePasswordBtn = $('togglePasswordBtn');
  if (togglePasswordBtn) {
    togglePasswordBtn.addEventListener('click', () => {
      const input = $('password');
      if (!input) return;

      const showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      togglePasswordBtn.setAttribute(
        'aria-label',
        showing ? 'แสดงรหัสผ่าน' : 'ซ่อนรหัสผ่าน'
      );
    });
  }

  async function loginAs(username){
    currentUser = username;
    try{ await window.storage.set('session', username, false); }catch(_){}
    if ($('currentUserLabel')) $('currentUserLabel').textContent = username;
    if ($('loginScreen')) $('loginScreen').classList.add('hidden');
    if ($('appScreen')) $('appScreen').classList.remove('hidden');
    viewDate = new Date();
    if ($('entryRows')) resetRows();
    await renderAll();
  }

  const logoutBtn = $('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async () => {
      try{ await window.storage.delete('session', false); }catch(_){}
      currentUser = null;
      if ($('username')) $('username').value = '';
      if ($('password')) $('password').value = '';
      if ($('appScreen')) $('appScreen').classList.add('hidden');
      if ($('loginScreen')) $('loginScreen').classList.remove('hidden');
    });
  }

  // ---------- Date navigation ----------
  $('prevDayBtn').addEventListener('click', () => { viewDate.setDate(viewDate.getDate()-1); renderAll(); });
  $('nextDayBtn').addEventListener('click', () => { viewDate.setDate(viewDate.getDate()+1); renderAll(); });

  function renderDateHeader(){
    const isToday = fmtDateKey(viewDate) === fmtDateKey(new Date());
    $('dateBig').textContent = viewDate.getDate() + ' ' + THAI_MONTHS[viewDate.getMonth()];
    $('dateWeekday').textContent = THAI_WEEKDAYS[viewDate.getDay()];
    $('todayBadgeWrap').innerHTML = isToday ? '<span class="today-badge">วันนี้</span>' : '';
    $('entriesTitle').textContent = isToday ? 'รายการวันนี้' : 'รายการวันที่ ' + viewDate.getDate() + ' ' + THAI_MONTHS[viewDate.getMonth()];
  }

  // ---------- Entries ----------
  async function loadEntries(){
    const key = 'entries:' + currentUser + ':' + fmtDateKey(viewDate);
    try{
      const res = await window.storage.get(key, false);
      return res ? JSON.parse(res.value) : [];
    }catch(_){ return []; }
  }
  async function saveEntries(list){
    const key = 'entries:' + currentUser + ':' + fmtDateKey(viewDate);
    await window.storage.set(key, JSON.stringify(list), false);
  }

  async function loadAllBranches(){
    try{
      const res = await window.storage.get('branches:' + currentUser, false);
      return res ? JSON.parse(res.value) : [];
    }catch(_){ return []; }
  }
  async function addBranchToHistory(branch){
    const list = await loadAllBranches();
    if(!list.includes(branch)){
      list.unshift(branch);
      await window.storage.set('branches:' + currentUser, JSON.stringify(list.slice(0,12)), false);
    }
  }

  function emptyStateHtml(){
    return '<div class="empty-state">'
      + '<svg width="72" height="52" viewBox="0 0 72 52" fill="none" xmlns="http://www.w3.org/2000/svg">'
      + '<rect x="4" y="10" width="64" height="38" rx="6" stroke="#D7DAE8" stroke-width="2"/>'
      + '<path d="M4 20H68" stroke="#D7DAE8" stroke-width="2"/>'
      + '<path d="M14 4V14" stroke="#F2A93B" stroke-width="3" stroke-linecap="round"/>'
      + '<path d="M58 4V14" stroke="#F2A93B" stroke-width="3" stroke-linecap="round"/>'
      + '<line x1="14" y1="30" x2="42" y2="30" stroke="#E3E5EF" stroke-width="2"/>'
      + '<line x1="14" y1="38" x2="34" y2="38" stroke="#E3E5EF" stroke-width="2"/>'
      + '</svg>'
      + '<p>ยังไม่มีรายการ — เริ่มบันทึกงานแรกของวันนี้</p>'
      + '</div>';
  }

  async function renderEntries(){
    const list = await loadEntries();
    $('entryCount').textContent = list.length;
    const wrap = $('entriesWrap');
    if(list.length === 0){
      wrap.innerHTML = emptyStateHtml();
      return;
    }
    const sorted = [...list].sort((a,b) => a.time.localeCompare(b.time));
    wrap.innerHTML = '<ul class="entries">' + sorted.map(e => (
      '<li>'
        + '<div class="entry-row">'
          + '<div class="entry-main">'
            + '<div class="entry-time">' + e.time + '</div>'
            + '<div class="entry-branch">' + escapeHtml(e.branch) + '</div>'
            + '<div class="entry-detail">' + escapeHtml(e.detail) + '</div>'
          + '</div>'
          + '<button class="del-btn" data-id="' + e.id + '" aria-label="ลบรายการ">✕</button>'
        + '</div>'
      + '</li>'
    )).join('') + '</ul>';

    wrap.querySelectorAll('.del-btn').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.getAttribute('data-id');
        const remaining = list.filter(e => String(e.id) !== id);
        await saveEntries(remaining);
        toast('ลบรายการแล้ว');
        renderEntries();
      });
    });
  }

  function escapeHtml(s){
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
  }

  async function renderBranchChips(){
    const branches = await loadAllBranches();
    $('branchChips').innerHTML = branches.length
      ? branches.map(b => '<span class="branch-chip">' + escapeHtml(b) + '</span>').join('')
      : '<span style="font-size:12.5px; color:var(--ink-soft);">ยังไม่มีข้อมูล</span>';
    $('branchOptions').innerHTML = branches.map(b => '<option value="' + escapeHtml(b) + '">').join('');
  }

  // ---------- Multi-row entry form: "+" adds another Branch/Detail row ----------
  function makeRowElement(){
    const row = document.createElement('div');
    row.className = 'entry-row-input';
    row.innerHTML =
        '<div class="field">'
      +   '<label>สาขา / แผนก</label>'
      +   '<input type="text" class="branch-field" list="branchOptions" placeholder="เช่น สาขาโคราช" required>'
      + '</div>'
      + '<div class="field">'
      +   '<label>รายละเอียดงานที่ทำ</label>'
      +   '<textarea class="detail-field" placeholder="เช่น ตั้งค่าเครื่องพิมพ์ / ตรวจสอบเครือข่าย" required></textarea>'
      + '</div>'
      + '<button type="button" class="row-action-btn"></button>';
    return row;
  }

  function updateRowActions(){
    const rows = Array.from($('entryRows').querySelectorAll('.entry-row-input'));
    rows.forEach((row, idx) => {
      const btn = row.querySelector('.row-action-btn');
      const isLast = idx === rows.length - 1;
      btn.onclick = null;
      if(isLast){
        btn.className = 'row-action-btn is-add';
        btn.textContent = '+';
        btn.setAttribute('aria-label', 'เพิ่มแถว');
        btn.onclick = () => {
          $('entryRows').appendChild(makeRowElement());
          updateRowActions();
        };
      } else {
        btn.className = 'row-action-btn is-remove';
        btn.textContent = '✕';
        btn.setAttribute('aria-label', 'ลบแถวนี้');
        btn.onclick = () => {
          row.remove();
          updateRowActions();
        };
      }
    });
  }

  function resetRows(){
    $('entryRows').innerHTML = '';
    $('entryRows').appendChild(makeRowElement());
    updateRowActions();
  }

  $('entryForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const rowEls = Array.from($('entryRows').querySelectorAll('.entry-row-input'));
    const drafts = rowEls.map(row => ({
      branch: row.querySelector('.branch-field').value.trim(),
      detail: row.querySelector('.detail-field').value.trim()
    })).filter(d => d.branch && d.detail);

    if(drafts.length === 0){ toast('กรอกสาขาและรายละเอียดอย่างน้อย 1 แถว'); return; }

    $('submitAllBtn').disabled = true;
    try{
      const list = await loadEntries();
      const now = new Date();
      const timeStr = String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');
      drafts.forEach((d, i) => {
        list.push({ id: Date.now() + i, time: timeStr, branch: d.branch, detail: d.detail });
      });
      await saveEntries(list);
      for(const d of drafts){ await addBranchToHistory(d.branch); }
      resetRows();
      toast(drafts.length > 1 ? ('บันทึก ' + drafts.length + ' รายการแล้ว') : 'บันทึกรายการแล้ว');
      await renderEntries();
      await renderBranchChips();
    } finally {
      $('submitAllBtn').disabled = false;
    }
  });

  async function renderAll(){
    renderDateHeader();
    await renderEntries();
    await renderBranchChips();
  }

  // ---------- Boot ----------
  // User authentication is now handled by PHP sessions.
  // Keep the old storage session boot disabled so it cannot conflict with PHP auth.
  async function boot(){
    if (!$('entryRows')) return;
    if (!currentUser) {
      currentUser = $('currentUserLabel')?.textContent?.trim() || null;
    }
    if (currentUser) {
      resetRows();
      await renderAll();
    }
  }

  boot();
})();
