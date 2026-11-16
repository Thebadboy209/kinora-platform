(()=>{'use strict';
 const $=(s,root=document)=>root.querySelector(s), $$=(s,root=document)=>Array.from(root.querySelectorAll(s));
 const toast=(message)=>{let el=$('#toast');if(!el)return;el.textContent=message;el.classList.add('show');clearTimeout(window.__toast);window.__toast=setTimeout(()=>el.classList.remove('show'),3200);};
 document.addEventListener('DOMContentLoaded',()=>{
  if(window.lucide)lucide.createIcons();
  const nav=$('.sidebar')||$('.admin-sidebar'), overlay=$('#navOverlay');
  $$('[data-toggle-nav],[data-toggle-adminnav]').forEach(btn=>btn.addEventListener('click',()=>{nav?.classList.toggle('open');overlay?.classList.toggle('show');}));
  overlay?.addEventListener('click',()=>{nav?.classList.remove('open');overlay.classList.remove('show');});
  $$('[data-confirm]').forEach(btn=>btn.addEventListener('click',e=>{if(!confirm(btn.dataset.confirm||'Are you sure?'))e.preventDefault();}));
  $$('[data-copy]').forEach(btn=>btn.addEventListener('click',async()=>{let url=new URL(btn.dataset.copy,location.origin+location.pathname.replace(/[^/]*$/,''));try{await navigator.clipboard.writeText(url.href);toast('Đã sao chép liên kết video.');}catch{prompt('Copy link:',url.href);}}));
  $$('[data-expand]').forEach(btn=>btn.addEventListener('click',()=>{const target=$('#'+btn.dataset.expand);target?.classList.toggle('expanded');btn.textContent=target?.classList.contains('expanded')?'Thu gọn':'Xem thêm';}));
  const player=$('#mainPlayer');player?.addEventListener('error',()=>{$('#playerError')?.removeAttribute('hidden');});
  const tableSearch=$('#tableSearch');tableSearch?.addEventListener('input',()=>{$$('.searchable-row').forEach(row=>{row.hidden=!row.textContent.toLowerCase().includes(tableSearch.value.trim().toLowerCase());});});
  const file=$('#videoFile'),drop=$('#dropZone'),browse=$('#browseVideo'),url=$('#video_url'), preview=$('#editorPreview'),place=$('#editorPreviewEmpty');
  const previewUrl=()=>{if(!preview||!url)return;const value=url.value.trim();if(/^https?:\/\//i.test(value)){preview.src=value;preview.style.display='block';if(place)place.style.display='none';}else{preview.removeAttribute('src');preview.load();preview.style.display='none';if(place)place.style.display='flex';}};
  url?.addEventListener('change',previewUrl);previewUrl();
  browse?.addEventListener('click',()=>file?.click());
  if(file&&drop){
    ['dragenter','dragover'].forEach(type=>drop.addEventListener(type,e=>{e.preventDefault();drop.classList.add('drag-over');}));
    ['dragleave','drop'].forEach(type=>drop.addEventListener(type,e=>{e.preventDefault();drop.classList.remove('drag-over');}));
    drop.addEventListener('drop',e=>{if(e.dataTransfer?.files?.[0])upload(e.dataTransfer.files[0]);});
    file.addEventListener('change',()=>{if(file.files?.[0])upload(file.files[0]);});
  }
  function upload(fileObj){
    const msg=$('#uploadMessage'), prog=$('#uploadProgress'), fill=$('#progressFill');
    const set=(txt,err=false)=>{if(msg){msg.textContent=txt;msg.style.color=err?'#ed9494':'#a9bddb';}};
    if(fileObj.size>1073741824){set('Tệp vượt quá 1 GiB.',true);return;}
    if(!/\.(mp4|webm|mov|m4v)$/i.test(fileObj.name)){set('Chỉ hỗ trợ MP4, WebM, MOV, M4V.',true);return;}
    const token=$('#editorForm input[name="csrf"]')?.value;
    const form=new FormData();form.append('csrf',token||'');form.append('video',fileObj);
    const xhr=new XMLHttpRequest();xhr.open('POST',location.pathname.replace(/index\.php$/,'')+'upload.php');xhr.timeout=360000;
    if(prog)prog.hidden=false;if(fill)fill.style.width='0%';set('Đang upload '+fileObj.name+' ('+Math.round(fileObj.size/1048576)+' MB)...');
    xhr.upload.onprogress=e=>{if(e.lengthComputable && fill)fill.style.width=Math.round(e.loaded/e.total*100)+'%';};
    xhr.onload=()=>{let res;try{res=JSON.parse(xhr.responseText);}catch{set('Máy chủ trả về dữ liệu không hợp lệ. Kiểm tra giới hạn hosting hoặc lỗi PHP.',true);return;}
      if(xhr.status>=200&&xhr.status<300&&res.ok){url.value=res.url;url.dispatchEvent(new Event('change'));set('Đã upload lên pone.rs. Kiểm tra khả năng phát trước khi xuất bản.');toast('Upload hoàn tất · URL đã được điền.');}
      else set(res.error||'Upload thất bại.',true);
    };
    xhr.onerror=()=>set('Không kết nối được máy chủ. Kiểm tra kết nối và PHP upload limit.',true);
    xhr.ontimeout=()=>set('Upload timeout. Thử tệp nhỏ hơn hoặc kiểm tra máy chủ.',true);xhr.send(form);
  }
 });
})();
