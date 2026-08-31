document.addEventListener('DOMContentLoaded',()=>{
  const reduced=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const header=document.querySelector('.site-header');
  const syncHeader=()=>header&&header.classList.toggle('scrolled',window.scrollY>18);
  syncHeader();window.addEventListener('scroll',syncHeader,{passive:true});

  const word=document.querySelector('[data-kinetic-word]');
  if(word&&!reduced){
    const words=['notice.','remember.','trust.','use.'];let i=0;
    window.setInterval(()=>{word.classList.add('swap');window.setTimeout(()=>{i=(i+1)%words.length;word.textContent=words[i];word.classList.remove('swap');},240);},2600);
  }

  const rail=document.querySelector('.capability-track');
  if(rail&&rail.children.length&&!rail.dataset.cloned){
    rail.innerHTML+=rail.innerHTML;rail.dataset.cloned='true';
  }
});
