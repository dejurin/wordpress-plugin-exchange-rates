(() => {
  'use strict';
  const names={centralbank:'Central Bank · six badges',editorialrn:'Editorial RN · BCRA converter',katuru:'Katuru · narrow table',morningtelegraph:'Morning Telegraph · full converter',sovereign:'Sovereign Finance · colored badge',todayafrica:'Today Africa · regions and small values'};
  let report,selected,pending={},running=false,queue=[],batch=[];
  const select=document.querySelector('#case'),width=document.querySelector('#width');
  const origins={baseline:'http://127.0.0.1:19334',candidate:'http://127.0.0.1:19335'};
  function show(id,size) {
    selected=report.cases.find(c=>c.id===id&&c.width===Number(size));
    if(!selected)throw new Error('Unobserved viewport');
    const sizes=report.cases.filter(c=>c.id===id).map(c=>c.width);
    [...width.options].forEach(o=>{o.disabled=!sizes.includes(Number(o.value));});
    select.value=id;width.value=String(size);pending={};
    document.querySelector('#status').textContent=selected.status;
    document.querySelector('#description').textContent=`${names[id]} · public version 1.4.2 · ${size} px viewport. Local images include the footer and a following-content marker.`;
    document.querySelector('#source').href=report.sources[id];
    const publicCapture=report.publicImages[`${id}-${size}`];
    document.querySelector('#public').src=`public/${publicCapture.file}`;
    document.querySelector('#public-note').textContent=publicCapture.note;
    document.querySelector('#public-title').textContent=`Public 1.4.2 · ${publicCapture.viewport} px capture`;
    for(const side of ['baseline','candidate']) {
      const image=document.querySelector(`#${side}-image`);
      image.src=`screenshots/${selected.versions[side].screenshot}`;
      image.onload=()=>{image.style.width=`${image.naturalWidth/2}px`;};
      const frame=document.querySelector(`#${side}`);frame.style.width=`${size}px`;
      frame.src=`${origins[side]}/?er_public_case=${id}&er_public_size=${size}&er_public_measure=1`;
    }
    const original=document.querySelector('#public');original.onload=()=>{
      original.style.width=`${original.naturalWidth/publicCapture.dpr}px`;
      const crop=publicCapture.crop||{x:8,y:8,width:original.naturalWidth/publicCapture.dpr-16,height:original.naturalHeight/publicCapture.dpr-16};
      const holder=document.querySelector('#public-crop');holder.style.width=`${crop.width}px`;holder.style.height=`${crop.height}px`;
      original.style.left=`${-crop.x}px`;original.style.top=`${-crop.y}px`;
    };
    document.querySelector('#modal').src=`screenshots/${selected.versions.candidate.modalScreenshot}`;
    document.querySelector('#findings').textContent=selected.status==='FAIL'||selected.status==='ERROR'
      ? 'FAILED: inspect the recorded errors and measured differences. This composition is not accepted.'
      : selected.knownNumericAxisReview?.length
      ? 'DESIGN REVIEW: the accepted price-axis correction reserves 18 px beside Price cells and reduces the nested base input width by the same amount. Raw differences remain recorded; unrelated changes still fail.'
      : selected.knownBorderReview.length
      ? 'REVIEW: converter borders changed from faint gray to #858b93 for visibility. Geometry, values and input behavior match. This accepted difference remains recorded as DESIGN REVIEW.'
      : selected.fidelity.length ? 'REVIEW: public-to-local body differences remain. Inspect the measured evidence before accepting reproduction fidelity.'
      : 'Body geometry, typography, values and currency order match the observed composition. Footer and modal are intentional changes and remain subject to visual acceptance.';
    document.querySelector('#metrics').textContent=JSON.stringify({chromium:selected},null,2);
  }
  window.addEventListener('message',event=>{
    const side=Object.keys(origins).find(k=>origins[k]===event.origin&&event.source===document.querySelector(`#${k}`).contentWindow);
    const data=event.data;
    if(!side||!selected||data?.type!=='er-public-measure'||data.id!==selected.id||Number(data.size)!==selected.width)return;
    pending[side]=data.result;
    if(!pending.baseline||!pending.candidate)return;
    const result={id:selected.id,width:selected.width,baseline:pending.baseline,candidate:pending.candidate};
    document.querySelector('#metrics').textContent=JSON.stringify(result,null,2);
    if(running){
      batch.push(result);document.querySelector('#all-results').textContent=JSON.stringify(batch);
      document.querySelector('#progress').textContent=`${batch.length}/${report.cases.length} compositions measured in this browser`;
      const next=queue.shift();
      if(next)show(next.id,next.width);else {running=false;document.querySelector('#measure-all').disabled=false;select.disabled=false;width.disabled=false;}
    }
  });
  select.addEventListener('change',()=>{
    const sizes=report.cases.filter(c=>c.id===select.value).map(c=>c.width);
    show(select.value,sizes.includes(Number(width.value))?width.value:sizes[0]);
  });
  width.addEventListener('change',()=>show(select.value,width.value));
  document.querySelector('#measure-all').addEventListener('click',()=>{
    running=true;batch=[];queue=[...report.cases];select.disabled=true;width.disabled=true;document.querySelector('#measure-all').disabled=true;
    const first=queue.shift();show(first.id,first.width);
  });
  fetch('report-data.json').then(r=>{if(!r.ok)throw new Error('Missing report');return r.json();}).then(data=>{
    report=data;for(const id of Object.keys(names)){const option=document.createElement('option');option.value=id;option.textContent=names[id];select.append(option);}
    document.querySelector('#receipt').textContent=`Run: ${report.finishedAt} · ${report.cases.length} configurations · ${JSON.stringify(report.counts)} · Candidate SHA-256 ${report.artifacts.candidate_sha256}`;
    show('editorialrn',1280);
  }).catch(error=>{document.querySelector('#status').textContent=error.message;});
})();
