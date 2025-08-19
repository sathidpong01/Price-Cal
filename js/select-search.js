
(function(){
  if (window.__selectSearchInstalled__) return; window.__selectSearchInstalled__=true;
  function enhanceSelect(sel){
    if(!sel || sel.dataset.searchableBound) return;
    sel.dataset.searchableBound = '1';
    var wrap=document.createElement('div'); wrap.className='select-search-wrap';
    var input=document.createElement('input'); input.type='text'; input.className='select-search-input'; input.placeholder='พิมพ์เพื่อค้นหา...';
    sel.parentNode.insertBefore(wrap, sel);
    wrap.appendChild(input); wrap.appendChild(sel);
    var opts = Array.from(sel.options);
    function filter(){
      var q = input.value.trim().toLowerCase();
      opts.forEach(function(o,idx){
        // keep the placeholder option visible
        if (idx===0 && (o.value==='' && /เลือก/.test(o.textContent))) { o.hidden=false; return; }
        var txt = (o.textContent||'').toLowerCase();
        o.hidden = q && txt.indexOf(q)===-1;
      });
    }
    input.addEventListener('input', filter);
    // Try focus typing UX
    sel.addEventListener('focus', function(){ if(document.activeElement!==input){ input.focus(); input.select(); } });
  }
  function init(){
    document.querySelectorAll('select[data-searchable="1"]').forEach(enhanceSelect);
  }
  document.addEventListener('DOMContentLoaded', init);
  window.initSearchableSelects = init;
})();
