<?php /* _galaxy.php — original galaxy background, no cursor interaction */ ?>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Share+Tech+Mono&display=swap" rel="stylesheet">
<style>
  body{background:#02040a !important;position:relative;overflow:hidden;}
  #galaxyBG{position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;pointer-events:none;}
  .scanlines{position:fixed;inset:0;z-index:1;pointer-events:none;background:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,0,0,0.06) 2px,rgba(0,0,0,0.06) 4px);}
  .card{position:relative;z-index:10 !important;background:rgba(5,12,28,0.84) !important;backdrop-filter:blur(18px);border-color:rgba(0,245,255,0.25) !important;color:#e8f4ff !important;box-shadow:0 0 40px rgba(0,245,255,0.06),0 8px 32px rgba(0,0,0,0.6) !important;}
  .card h1,.card .logo{color:#e8f4ff !important;}
  .card .sub,.card p{color:rgba(255,255,255,0.5) !important;}
  .card input,.card select{background:rgba(0,20,10,0.6) !important;border-color:rgba(0,245,255,0.2) !important;color:#e8f4ff !important;}
  .card input::placeholder{color:rgba(255,255,255,0.25) !important;}
  .card input:focus{border-color:rgba(0,245,255,0.7) !important;box-shadow:0 0 0 3px rgba(0,245,255,0.1) !important;}
  .card .note{color:rgba(0,245,255,0.35) !important;}
  .card .alert{background:rgba(255,248,200,0.07) !important;border-color:rgba(255,224,130,0.3) !important;color:rgba(255,230,120,0.85) !important;}
  select option{background:#0a1a0f;color:#e8f4ff;}
</style>
<canvas id="galaxyBG"></canvas>
<script>
(function(){
  const cv=document.getElementById("galaxyBG");
  if(!cv)return;
  const cx=cv.getContext("2d");
  let W,H,CX,CY;
  function resize(){W=cv.width=window.innerWidth;H=cv.height=window.innerHeight;CX=W/2;CY=H/2;}
  resize();window.addEventListener("resize",resize);
  const BIN="010011011001110100101100111010010110011101001011001110100101";
  const rings=[
    {a:0,speed:0.0022,rx:0.40,ry:0.13,tilt:0.30,color:[0,245,255],alpha:0.70,fs:12,offset:0},
    {a:0,speed:0.0015,rx:0.50,ry:0.165,tilt:0.25,color:[0,245,255],alpha:0.50,fs:11,offset:2.1},
    {a:0,speed:0.0010,rx:0.60,ry:0.20,tilt:0.20,color:[57,255,20],alpha:0.38,fs:10,offset:4.2},
    {a:0,speed:0.0028,rx:0.32,ry:0.10,tilt:0.36,color:[191,0,255],alpha:0.35,fs:10,offset:1.1},
    {a:0,speed:0.0008,rx:0.70,ry:0.23,tilt:0.15,color:[255,180,0],alpha:0.22,fs:9,offset:3.3},
  ];
  const bgStars=Array.from({length:200},()=>({
    x:Math.random(),y:Math.random(),
    r:Math.random()*1.1+0.2,
    a:Math.random()*0.35+0.05,
    twinkle:Math.random()*Math.PI*2,
    ts:0.015+Math.random()*0.025,
    c:Math.random()>0.7?[255,0,110]:Math.random()>0.5?[57,255,20]:[0,245,255]
  }));
  function shield(t){
    const s=Math.min(W,H)*0.13;
    cx.save();cx.translate(CX,CY);
    const h=cx.createRadialGradient(0,0,s*0.1,0,0,s*1.4);
    h.addColorStop(0,"rgba(0,245,255,0.15)");h.addColorStop(0.5,"rgba(0,80,180,0.07)");h.addColorStop(1,"transparent");
    cx.fillStyle=h;cx.beginPath();cx.arc(0,0,s*1.4,0,Math.PI*2);cx.fill();
    for(let i=0;i<3;i++){const pr=s*(0.85+i*0.2)+Math.sin(t*0.04+i*1.3)*s*0.03;cx.beginPath();cx.arc(0,0,pr,0,Math.PI*2);cx.strokeStyle=`rgba(0,245,255,${0.10-i*0.025})`;cx.lineWidth=1.2;cx.stroke();}
    cx.beginPath();cx.moveTo(0,-s*0.62);cx.bezierCurveTo(s*0.58,-s*0.62,s*0.58,s*0.10,0,s*0.66);cx.bezierCurveTo(-s*0.58,s*0.10,-s*0.58,-s*0.62,0,-s*0.62);cx.closePath();
    const bg=cx.createLinearGradient(-s*.5,-s*.6,s*.5,s*.6);bg.addColorStop(0,"rgba(8,25,55,0.93)");bg.addColorStop(0.45,"rgba(12,50,95,0.90)");bg.addColorStop(1,"rgba(4,15,35,0.95)");cx.fillStyle=bg;cx.fill();
    cx.beginPath();cx.moveTo(0,-s*0.62);cx.bezierCurveTo(s*0.58,-s*0.62,s*0.58,s*0.10,0,s*0.66);cx.bezierCurveTo(-s*0.58,s*0.10,-s*0.58,-s*0.62,0,-s*0.62);cx.closePath();
    const gd=cx.createLinearGradient(-s*.5,-s*.5,s*.5,s*.5);gd.addColorStop(0,"rgba(200,160,40,0.9)");gd.addColorStop(0.5,"rgba(255,215,80,1)");gd.addColorStop(1,"rgba(175,125,15,0.9)");cx.strokeStyle=gd;cx.lineWidth=s*0.038;cx.stroke();
    cx.beginPath();cx.moveTo(0,-s*0.44);cx.bezierCurveTo(s*0.38,-s*0.44,s*0.38,s*0.06,0,s*0.48);cx.bezierCurveTo(-s*0.38,s*0.06,-s*0.38,-s*0.44,0,-s*0.44);cx.closePath();cx.strokeStyle="rgba(255,215,80,0.25)";cx.lineWidth=s*0.015;cx.stroke();
    const lk=s*0.17;cx.beginPath();cx.arc(0,-lk*0.38,lk*0.50,Math.PI,Math.PI*2);cx.strokeStyle="rgba(0,245,255,0.92)";cx.lineWidth=lk*0.17;cx.stroke();
    const lb=cx.createLinearGradient(-lk*.6,0,lk*.6,lk*1.1);lb.addColorStop(0,"rgba(0,200,235,0.9)");lb.addColorStop(1,"rgba(0,100,160,0.9)");cx.fillStyle=lb;
    cx.beginPath();cx.roundRect(-lk*0.6,0,lk*1.2,lk*1.05,lk*0.14);cx.fill();
    cx.beginPath();cx.arc(0,lk*0.32,lk*0.17,0,Math.PI*2);cx.fillStyle="rgba(0,245,255,0.97)";cx.fill();cx.fillRect(-lk*0.055,lk*0.32,lk*0.11,lk*0.30);
    cx.textAlign="center";cx.font=`bold ${s*0.10}px Orbitron,monospace`;cx.fillStyle="rgba(0,245,255,0.82)";cx.fillText("CYBER",0,s*0.17);
    cx.font=`bold ${s*0.074}px Orbitron,monospace`;cx.fillStyle="rgba(0,245,255,0.62)";cx.fillText("PROTECTION",0,s*0.31);cx.restore();
  }
  function drawRing(ring,t){
    const n=Math.floor(2*Math.PI*ring.rx*W/ring.fs/1.6);cx.save();cx.translate(CX,CY);const pts=[];
    for(let i=0;i<n;i++){
      const theta=(i/n)*Math.PI*2+ring.a+ring.offset;
      const ex=Math.cos(theta)*ring.rx*W;const ey_flat=Math.sin(theta)*ring.ry*H;
      const ey=ey_flat*Math.cos(ring.tilt);const ez=ey_flat*Math.sin(ring.tilt);
      const depth=(ez+ring.ry*H)/(ring.ry*H*2);const depthAlpha=0.15+depth*0.85;
      const brightSpot=Math.abs(Math.sin(theta-ring.a*0.5))>0.93;
      pts.push({ex,ey,depth,depthAlpha,brightSpot,charIdx:i});
    }
    pts.sort((a,b)=>a.depth-b.depth);
    for(const p of pts){
      const [r,g,b]=ring.color;const a=ring.alpha*p.depthAlpha*(0.7+Math.sin(t*0.02+p.charIdx)*0.3);
      cx.font=ring.fs+"px 'Share Tech Mono',monospace";
      cx.fillStyle=p.brightSpot?`rgba(255,255,255,${Math.min(a*1.8,1)})`:`rgba(${r},${g},${b},${a})`;
      cx.fillText(BIN[(p.charIdx+Math.floor(t*0.08))%BIN.length],p.ex,p.ey);
    }
    cx.restore();
  }
  let fr=0;
  function draw(){
    fr++;
    cx.fillStyle="rgba(2,4,10,0.20)";cx.fillRect(0,0,W,H);
    const space=cx.createRadialGradient(CX,CY,0,CX,CY,Math.max(W,H)*0.75);
    space.addColorStop(0,"rgba(0,10,30,0.10)");space.addColorStop(0.4,"rgba(0,5,18,0.08)");space.addColorStop(1,"rgba(0,0,0,0)");
    cx.fillStyle=space;cx.fillRect(0,0,W,H);
    cx.strokeStyle="rgba(0,245,255,0.022)";cx.lineWidth=0.5;
    for(let x=0;x<W;x+=44){cx.beginPath();cx.moveTo(x,0);cx.lineTo(x,H);cx.stroke();}
    for(let y=0;y<H;y+=44){cx.beginPath();cx.moveTo(0,y);cx.lineTo(W,y);cx.stroke();}
    for(const s of bgStars){
      s.twinkle+=s.ts;const a=s.a*(0.5+Math.sin(s.twinkle)*0.5);
      cx.beginPath();cx.arc(s.x*W,s.y*H,s.r,0,Math.PI*2);
      cx.fillStyle=`rgba(${s.c[0]},${s.c[1]},${s.c[2]},${a})`;cx.fill();
    }
    for(const r of rings) r.a+=r.speed;
    drawRing(rings[4],fr);drawRing(rings[2],fr);drawRing(rings[3],fr);
    shield(fr);
    drawRing(rings[1],fr);drawRing(rings[0],fr);
    const sy=(fr*1.3)%H;const sg=cx.createLinearGradient(0,sy-3,0,sy+3);
    sg.addColorStop(0,"transparent");sg.addColorStop(0.5,"rgba(0,245,255,0.028)");sg.addColorStop(1,"transparent");
    cx.fillStyle=sg;cx.fillRect(0,sy-3,W,6);
    requestAnimationFrame(draw);
  }
  draw();
})();
</script>
<div class="scanlines"></div>
