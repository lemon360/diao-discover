/* Discover: dependency-free progressive enhancement. */
(() => {
  'use strict';
  const $ = (s, root = document) => root.querySelector(s);
  const $$ = (s, root = document) => [...root.querySelectorAll(s)];
  let toastTimer;
  function toast(message) {
    const el = $('.toast'); if (!el) return;
    el.textContent = message; el.hidden = false;
    clearTimeout(toastTimer); toastTimer = setTimeout(() => { el.hidden = true; }, 3500);
  }
  let saved = [];
  try { const value = JSON.parse(localStorage.getItem('dh-saved') || '[]'); saved = Array.isArray(value) ? [...new Set(value.filter(id => Number.isInteger(id) && id > 0))].slice(0,100) : []; } catch (_) { /* Storage can be disabled. */ }
  const savedUrl = () => { const url = new URL(DH.home); url.searchParams.set('saved','1'); if (saved.length) url.searchParams.set('ids', saved.join(',')); return url.href; };
  function renderBookmarks() {
    $$('[data-bookmark]').forEach(button => {
      const active = saved.includes(Number(button.dataset.bookmark));
      button.classList.toggle('is-active', active); button.setAttribute('aria-pressed', String(active));
      button.title = active ? '取消收藏' : '收藏到当前浏览器';
      const label = $('span', button); if (label) label.textContent = active ? '已收藏' : '收藏';
    });
    $$('.saved-count').forEach(el => { el.textContent = String(saved.length); });
    $$('[data-saved-link]').forEach(el => { el.href = savedUrl(); });
  }
  renderBookmarks();
  const currentUrl = new URL(location.href);
  if (currentUrl.searchParams.has('saved') && !currentUrl.searchParams.has('ids') && saved.length) location.replace(savedUrl());
  $$('[data-bookmark]').forEach(button => button.addEventListener('click', () => {
    const id = Number(button.dataset.bookmark), removing = saved.includes(id);
    const next = removing ? saved.filter(value => value !== id) : [id,...saved].slice(0,100);
    try { localStorage.setItem('dh-saved', JSON.stringify(next)); } catch (_) { toast('浏览器禁止本地存储，未能保存收藏。'); return; }
    saved = next; renderBookmarks(); toast(removing ? '已取消收藏' : '已收藏，可在左侧「我的收藏」查看');
    if (currentUrl.searchParams.has('saved') && removing) location.assign(savedUrl());
  }));
  window.addEventListener('storage', event => { if (event.key === 'dh-saved') { try { const value=JSON.parse(event.newValue || '[]'); saved=Array.isArray(value)?value.filter(Number.isInteger).slice(0,100):[];renderBookmarks(); } catch (_) {} } });
  $$('[data-theme-toggle]').forEach(button => button.addEventListener('click', () => {
    const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = next;
    try { localStorage.setItem('dh-theme', next); } catch (_) {}
    button.setAttribute('aria-label', next === 'dark' ? '切换浅色模式' : '切换深色模式');
  }));
  const panel = $('#mobile-panel'), toggle = $('.mobile-toggle');
  function closeMenu() { if (!panel || !toggle) return; panel.hidden = true; toggle.setAttribute('aria-expanded', 'false'); }
  toggle?.addEventListener('click', () => { const open=panel.hidden;panel.hidden=!open;toggle.setAttribute('aria-expanded',String(open)); if(open) $('a',panel)?.focus(); });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && panel && !panel.hidden) {closeMenu();toggle.focus();}
    if (event.key === '/' && !event.ctrlKey && !event.metaKey && !/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName) && !document.activeElement.isContentEditable) {
      const search = $('#header-s'); if (search && search.getClientRects().length) {event.preventDefault();search.focus();}
    }
  });
  document.addEventListener('click', event => { if (panel && !panel.hidden && !panel.contains(event.target) && !toggle.contains(event.target)) closeMenu(); });
  async function copy(text) {
    try { await navigator.clipboard.writeText(text); toast('链接已复制，可以粘贴到微信或其他应用。'); }
    catch (_) { window.prompt('复制以下内容：', text); }
  }
  $$('[data-copy]').forEach(button => button.addEventListener('click', () => copy(button.dataset.copy)));
  const shareDialog = $('#dh-share-dialog');
  let shareTrigger;
  function closeShare() { if(!shareDialog)return;shareDialog.close();document.body.classList.remove('has-share-dialog');shareTrigger?.focus(); }
  $$('[data-share]').forEach(button => button.addEventListener('click', () => {
    if(!shareDialog)return;
    shareTrigger=button;
    const url=encodeURIComponent(button.dataset.url),title=encodeURIComponent(button.dataset.title);
    const links={weibo:`https://service.weibo.com/share/share.php?url=${url}&title=${title}`,qq:`https://connect.qq.com/widget/shareqq/index.html?url=${url}&title=${title}`,qzone:`https://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?url=${url}&title=${title}`,x:`https://twitter.com/intent/tweet?url=${url}&text=${title}`,telegram:`https://t.me/share/url?url=${url}&text=${title}`};
    $$('[data-share-network]',shareDialog).forEach(a=>{a.href=links[a.dataset.shareNetwork];});
    $('[data-share-title]',shareDialog).textContent=button.dataset.title;
    $('[data-share-copy]',shareDialog).onclick=()=>copy(button.dataset.url);
    $('[data-share-wechat]',shareDialog).onclick=()=>copy(button.dataset.url);
    shareDialog.showModal();document.body.classList.add('has-share-dialog');
  }));
  $('[data-share-close]')?.addEventListener('click',closeShare);
  shareDialog?.addEventListener('cancel',event=>{event.preventDefault();closeShare();});
  shareDialog?.addEventListener('click',event=>{const r=shareDialog.getBoundingClientRect();if(event.target===shareDialog&&(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom))closeShare();});
  function renderVote(id,data){$$(`[data-vote="${id}"]`).forEach(b=>{b.classList.toggle('is-active',data.active);b.setAttribute('aria-pressed',String(data.active));$('span',b).textContent=data.count>=1000?`${(data.count/1000).toFixed(1)}k`:String(data.count);});}
  let guestVoteToken='';
  async function voteFetch(path,options={}){
    const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),10000);
    try{const headers={'Content-Type':'application/json',...(DH.loggedIn?{'X-WP-Nonce':DH.nonce}:{}),...(options.headers||{})};const response=await fetch(`${DH.rest}${path}`,{...options,headers,credentials:'same-origin',cache:'no-store',signal:controller.signal});const data=await response.json();if(!response.ok)throw new Error(data.message||'推荐失败，请刷新后重试。');return data;}
    finally{clearTimeout(timer);}
  }
  const viewIds=[...new Set($$('[data-view-count]').map(el=>el.dataset.viewCount))].slice(0,100);
  function renderViews(id,count){$$(`[data-view-count="${id}"]`).forEach(el=>{el.textContent=count>=1000?`${(count/1000).toFixed(1)}k`:String(count);});}
  if(viewIds.length)voteFetch(`views?ids=${viewIds.join(',')}`).then(data=>Object.entries(data.counts||{}).forEach(([id,count])=>renderViews(id,count))).catch(()=>{});
  const viewPostId=Number(DH.viewPost)||0;
  let viewTimer,viewSent=false;
  function recordVisibleView(){
    clearTimeout(viewTimer);
    if(!viewPostId||viewSent||document.visibilityState!=='visible')return;
    const key=`dh-view-${viewPostId}`;
    try{if(Date.now()-Number(localStorage.getItem(key)||0)<1800000)return;}catch(_){}
    viewTimer=setTimeout(async()=>{
      viewSent=true;
      try{const data=await voteFetch(`view/${viewPostId}`,{method:'POST',body:'{}'});renderViews(viewPostId,data.count);try{localStorage.setItem(key,String(Date.now()));}catch(_){}}
      catch(_){viewSent=false;}
    },1500);
  }
  document.addEventListener('visibilitychange',recordVisibleView);recordVisibleView();
  const voteIds=[...new Set($$('[data-vote]').map(b=>b.dataset.vote))];
  const voteReady=voteIds.length?voteFetch(`votes?ids=${voteIds.join(',')}`).then(data=>{guestVoteToken=data.token||'';Object.entries(data.states||{}).forEach(([id,state])=>renderVote(id,state));}).catch(()=>{}):Promise.resolve();
  $$('[data-vote]').forEach(button => button.addEventListener('click', async () => {
    const id=button.dataset.vote, buttons=$$(`[data-vote="${id}"]`);
    buttons.forEach(b=>{b.disabled=true;});
    try {
      await voteReady;
      if(!DH.loggedIn&&!guestVoteToken){const session=await voteFetch('vote-session',{method:'POST',body:'{}'});guestVoteToken=session.token;}
      const active=button.getAttribute('aria-pressed')!=='true';
      const data=await voteFetch(`vote/${id}`,{method:'POST',headers:DH.loggedIn?{}:{'X-DH-Token':guestVoteToken},body:JSON.stringify({active})});
      renderVote(id,data);
      toast(data.active?'推荐成功，谢谢你的发现。':'已撤销推荐');
    } catch(error){toast(error.message||'网络异常，请稍后重试。');}
    finally{buttons.forEach(b=>{b.disabled=false;});}
  }));
  const content=$('.entry-content'), toc=$('.toc');
  if(content && toc){
    const headings=$$('h2,h3',content),nav=$('nav',toc);
    if(headings.length>=3){headings.forEach((heading,index)=>{if(!heading.id)heading.id=`section-${index+1}`;const a=document.createElement('a');a.href=`#${encodeURIComponent(heading.id)}`;a.textContent=heading.textContent;a.className=`level-${heading.tagName.slice(1)}`;nav.append(a);});toc.hidden=false;}
  }
  $$('.entry-content pre').forEach(pre=>{
    const code=$('code',pre);if(!code)return;
    const button=document.createElement('button');button.className='copy-code';button.textContent='复制代码';button.type='button';
    button.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(code.textContent);button.textContent='已复制';setTimeout(()=>{button.textContent='复制代码';},1800);}catch(_){window.prompt('复制代码：',code.textContent);}});pre.append(button);
  });
})();
