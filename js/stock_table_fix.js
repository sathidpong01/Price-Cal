
/*! Stock table column sync & sticky helper (vanilla JS) */
(function(){
  if (window.__stockSyncInstalled__) return; window.__stockSyncInstalled__ = true;

  function scrollbarWidth(){
    const d = document.createElement('div');
    d.style.cssText = 'position:absolute; top:-9999px; width:100px; height:100px; overflow:scroll;';
    document.body.appendChild(d);
    const w = d.offsetWidth - d.clientWidth;
    d.remove();
    return w;
  }

  function syncTables(headTable, bodyTable){
    if (!headTable || !bodyTable) return;
    const headCells = headTable.querySelectorAll('thead th');
    if (!headCells.length) return;

    headCells.forEach(th => th.style.width = '');
    bodyTable.querySelectorAll('tbody td').forEach(td => td.style.width = '');

    const widths = Array.from(headCells).map(th => Math.ceil(th.getBoundingClientRect().width));

    const headCols = headTable.querySelectorAll('colgroup col');
    const bodyCols = bodyTable.querySelectorAll('colgroup col');
    if (headCols.length === widths.length && bodyCols.length === widths.length) {
      widths.forEach((w,i)=>{ headCols[i].style.width = w+'px'; bodyCols[i].style.width = w+'px'; });
    } else {
      widths.forEach((w,i)=>{ if (headCells[i]) headCells[i].style.width = w+'px'; });
      const firstRowTds = bodyTable.querySelectorAll('tbody tr:first-child td');
      widths.forEach((w,i)=>{ if (firstRowTds[i]) firstRowTds[i].style.width = w+'px'; });
    }

    const scw = scrollbarWidth();
    const bodyWrapper = bodyTable.parentElement;
    if (bodyWrapper && bodyWrapper.scrollHeight > bodyWrapper.clientHeight && scw > 0) {
      headTable.style.marginRight = scw + 'px';
    } else {
      headTable.style.marginRight = '';
    }
  }

  function init(){
    var single = document.querySelector('#stockTable, table.stock-table');
    var syncContainer = document.querySelector('.stock-table-sync, [data-stock-sync]');
    if (single && !syncContainer) return;

    if (!syncContainer) return;

    var headTable = syncContainer.querySelector('table.stock-head') || (function(){var t=syncContainer.querySelector('table'); return t && t.querySelector('thead')? t : null;})();
    var all = syncContainer.querySelectorAll('table');
    var bodyTable = syncContainer.querySelector('table.stock-body') || (all.length>1? all[1] : null);
    if (!headTable || !bodyTable) return;

    [headTable, bodyTable].forEach(t => t.style.tableLayout='fixed');

    var doSync = function(){ syncTables(headTable, bodyTable); };
    doSync();
    window.addEventListener('resize', doSync);
    var ro1 = new ResizeObserver(doSync);
    var ro2 = new ResizeObserver(doSync);
    ro1.observe(headTable); ro2.observe(bodyTable);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else { init(); }
})();
