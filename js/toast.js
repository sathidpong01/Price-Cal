
(function(){
  if (window.__toastInstalled__) return; window.__toastInstalled__=true;
  function ensureContainer(){
    var c=document.getElementById('toast-container');
    if(!c){ c=document.createElement('div'); c.id='toast-container'; document.body.appendChild(c); }
    return c;
  }
  function iconFor(type){
    if(type==='success') return 'M10 15l-3.5-3.5 1.4-1.4L10 12.2l5.7-5.7 1.4 1.4z';
    if(type==='error')   return 'M6 6l12 12M18 6L6 18';
    return 'M12 3v2m0 14v2m9-9h-2M5 12H3m13.66 6.66l-1.41-1.41M6.34 6.34 4.93 4.93m12.73 0-1.41 1.41M6.34 17.66l-1.41 1.41';
  }
  function showToast(type, msg, opts){
    opts = opts || {}; var dur = opts.duration || 3000;
    var c=ensureContainer();
    var t=document.createElement('div'); t.className='toast '+(type||'info');
    var svg='<svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="'+iconFor(type)+'"/></svg>';
    t.innerHTML= svg + '<button class="close" aria-label="Close">&times;</button>' +
      '<div class="title">'+ (type==='success'?'สำเร็จ':'error'===type?'ผิดพลาด':'แจ้งเตือน') +'</div>' +
      '<div class="msg"></div>';
    t.querySelector('.msg').textContent = (msg==null?'':String(msg));
    c.appendChild(t);
    var hide=function(){ t.style.animation='toast-out .15s ease-in forwards'; setTimeout(function(){ t.remove(); },150); };
    t.querySelector('.close').addEventListener('click', hide);
    if(dur>0) setTimeout(hide, dur);
  }
  window.showToast = showToast;
  window.displayGlobalMessage = function(type, msg){ showToast(type==='error'?'error':type==='success'?'success':'info', msg); };
  // Override alert -> toast with heuristic for type
  var nativeAlert = window.alert;
  window.alert = function(msg){
    var s = (msg||'').toString();
    var type = /ผิดพลาด|error|ล้มเหลว/i.test(s) ? 'error' :
               /สำเร็จ|เรียบร้อย|อัปเดต|บันทึก/i.test(s) ? 'success' : 'info';
    showToast(type, s);
  };
})();
