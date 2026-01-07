<!-- Калькулятор стоимости (Honest Cost Calculator) -->
<div id="aircraft-cost-calc" style="font-family: system-ui, -apple-system, Segoe UI, Roboto, 'IBM Plex Sans', Arial, sans-serif; max-width:700px; margin:10px auto 0; color:#0f172a;">
  <h2 style="font-family: 'Playfair Display', serif; font-size: 32px; font-weight: 600; color: #000; text-align: center; margin: 0 0 30px;">Honest Cost Calculator</h2>
  <p class="calc-note" style="margin:0 0 16px; font-size:14px; color:#475569; text-align:center;">
    Engine and propeller are <strong>excluded</strong> for all models. Their cost varies widely depending on manufacturer, specifications, and condition, so to keep comparisons fair they are left out and discussed separately.
  </p>

  <style>
    .toggle-btn {
      width:20px;height:20px;border:2px solid #94a3b8;border-radius:4px;background:#fff;
      display:inline-flex;align-items:center;justify-content:center;cursor:pointer;line-height:1;
    }
    .toggle-btn.on {border-color:#059669;background:#ecfdf5;}
    .toggle-btn.on::after {content:"✓";font-size:14px;color:#059669;}
    .row-dim {opacity:.45}
    .muted {color:#64748b}
  </style>

  <div style="overflow:auto;">
    <table id="result" style="width:100%; border-collapse:collapse; margin:0 auto;">
      <thead>
        <tr style="background:#e5e7eb; color:#111;">
          <th style="text-align:left; padding:10px 12px; border-right:1px solid #d1d5db;">Item</th>
          <th style="text-align:right; padding:10px 12px; border-right:1px solid #d1d5db;">Van's RV-10</th>
          <th style="text-align:right; padding:10px 12px; border-right:1px solid #d1d5db;">Sling HW</th>
          <th style="text-align:right; padding:10px 12px;">Sinbad</th>
        </tr>
      </thead>
      <tbody id="rows"></tbody>
      <tfoot>
        <tr>
          <th style="text-align:left; padding:10px 12px; background:#f1f5f9;">Total</th>
          <th id="tot-rv10" style="text-align:right; padding:10px 12px; background:#f1f5f9;"></th>
          <th id="tot-sling" style="text-align:right; padding:10px 12px; background:#f1f5f9;"></th>
          <th id="tot-sinbad" style="text-align:right; padding:10px 12px; background:#f1f5f9;"></th>
        </tr>
      </tfoot>
    </table>
  </div>

  <div style="margin-top:12px; font-size:13px; color:#475569; display:grid; gap:6px; text-align:left;">
    <div><span style="font-weight:600;">*</span> Turn-key build labor reference: Sling TSi/HW flat-fee <strong>$90,000</strong> — <a href="https://www.customaircraftbuilders.com/wp-content/uploads/2024/04/Build-Budget-04-2024-v4-interactive.pdf" target="_blank" rel="noopener">Custom Aircraft Builders (Apr 2024)</a>. RV-10 estimated at <strong>$128,000</strong>. U.S. centers may quote higher.</div>
    <div><span style="font-weight:600;">**</span> Sinbad kit and 100% assembly labor are current project prices.</div>
  </div>
</div>

<script>
  (function(){
    const cfg=window.CALC_CONFIG={
      common:{avionicsVFR:15000,paintBase:12000,propPrice:26213},
      aircraft:{
        sinbad:{name:"Sinbad",kit:45000,labor100:45000},
        rv10:{name:"Van's RV-10",kit:85900,labor100:128000},
        sling:{name:"Sling High Wing",kit:83500,labor100:90000}
      }
    };
    const $=(sel)=>document.querySelector(sel);
    const fmt=(n)=>new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',maximumFractionDigits:0}).format(n);
    const fmtCell=(n,mark)=>`${fmt(n)}${mark?`<sup>${mark}</sup>`:''}`;
    const rowsEl=$('#rows');
    const tot={rv10:$('#tot-rv10'),sling:$('#tot-sling'),sinbad:$('#tot-sinbad')};
    const rowStates={kit:true,prop:true,av:true,paint:true,labor:true};

    function compute(){
      const lines=[
        {label:'Kit',key:'kit',values:{rv10:cfg.aircraft.rv10.kit,sling:cfg.aircraft.sling.kit,sinbad:cfg.aircraft.sinbad.kit}},
        {label:'Avionics (VFR)',key:'av',values:{rv10:cfg.common.avionicsVFR,sling:cfg.common.avionicsVFR,sinbad:cfg.common.avionicsVFR}},
        {label:'Paint (base)',key:'paint',values:{rv10:cfg.common.paintBase,sling:cfg.common.paintBase,sinbad:cfg.common.paintBase}},
        {label:'Turn-key build labor cost',key:'labor',values:{rv10:cfg.aircraft.rv10.labor100,sling:cfg.aircraft.sling.labor100,sinbad:cfg.aircraft.sinbad.labor100}}
      ];
      rowsEl.innerHTML='';
      const totals={rv10:0,sling:0,sinbad:0};
      lines.forEach(line=>{
        const marks={rv10:'',sling:'',sinbad:''};
        if(line.key==='kit'){marks.sinbad='**';}
        if(line.key==='labor'){marks.rv10='*';marks.sling='*';marks.sinbad='**';}
        const active=rowStates[line.key]!==false;
        const tr=document.createElement('tr');
        if(!active) tr.classList.add('row-dim');
        const toggle=`<button class="toggle-btn ${active?'on':'off'}" data-key="${line.key}" aria-pressed="${active}"></button>`;
        tr.innerHTML=`
          <td style="padding:10px 12px; border-bottom:1px solid #e2e8f0;">
            <div style="display:flex;align-items:center;gap:8px;">${toggle}<span>${line.label}</span></div>
          </td>
          <td style="padding:10px 12px; border-bottom:1px solid #e2e8f0; text-align:right;">${fmtCell(line.values.rv10,marks.rv10)}</td>
          <td style="padding:10px 12px; border-bottom:1px solid #e2e8f0; text-align:right;">${fmtCell(line.values.sling,marks.sling)}</td>
          <td style="padding:10px 12px; border-bottom:1px solid #e2e8f0; text-align:right;">${fmtCell(line.values.sinbad,marks.sinbad)}</td>`;
        rowsEl.appendChild(tr);
        if(active){
          totals.rv10+=line.values.rv10;
          totals.sling+=line.values.sling;
          totals.sinbad+=line.values.sinbad;
        }
      });
      tot.rv10.textContent=fmt(totals.rv10);
      tot.sling.textContent=fmt(totals.sling);
      tot.sinbad.textContent=fmt(totals.sinbad);
      rowsEl.querySelectorAll('.toggle-btn').forEach(btn=>{
        btn.addEventListener('click',()=>{
          const key=btn.getAttribute('data-key');
          rowStates[key]=!(rowStates[key]!==false);
          compute();
        });
      });
    }
    compute();
  })();
</script>
