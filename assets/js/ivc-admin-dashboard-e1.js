/* Fase 4.9.1-E.1 · Gráficas con datos suministrados por PHP/MySQL */
(function(){
  'use strict';
  var el=document.getElementById('ivcDashboardData');
  if(!el||typeof Chart==='undefined'){return;}
  var d;try{d=JSON.parse(el.textContent);}catch(e){return;}
  var palette=['#1767bd','#29a2c4','#f6b445','#45b3a6','#766cd3','#e5838c'];
  var optBase={responsive:true,maintainAspectRatio:false,legend:{display:false},
    tooltips:{backgroundColor:'#153a5d',cornerRadius:9,displayColors:false},
    animation:{duration:650},
    scales:{xAxes:[{gridLines:{display:false},ticks:{fontColor:'#71839a',maxRotation:0}}],
             yAxes:[{gridLines:{color:'#eef3f8',zeroLineColor:'#eef3f8'},ticks:{fontColor:'#71839a',beginAtZero:true}}]}};
  function chart(id,type,labels,values,color,custom){
    var cv=document.getElementById(id);if(!cv){return;}
    var opts=JSON.parse(JSON.stringify(optBase));
    if(custom){custom(opts);}
    if(!values.length||values.every(function(v){return Number(v)===0;})){
      var p=document.createElement('p');p.className='text-center text-muted small mt-5';
      p.textContent='Sin datos para este periodo';cv.parentNode.appendChild(p);cv.style.display='none';return;
    }
    new Chart(cv.getContext('2d'),{type:type,data:{labels:labels,datasets:[{
      data:values,label:'Datos registrados',
      borderColor:color||palette[0],backgroundColor:Array.isArray(color)?color:(type==='line'?'rgba(23,103,189,.10)':color||palette[0]),
      borderWidth:type==='line'?3:0,pointBackgroundColor:'#fff',pointBorderColor:'#1767bd',pointRadius:3,
      lineTension:.3,fill:type==='line',barPercentage:.64,categoryPercentage:.7
    }]},options:opts});
  }
  var months=(d.meses||[]).map(function(m){var parts=m.split('-');var date=new Date(Number(parts[0]),Number(parts[1])-1,1);return date.toLocaleDateString('es-MX',{month:'short',year:'2-digit'});});
  chart('ivcVentasMensuales','line',months,d.ventas||[],palette[0],function(o){
    o.tooltips.callbacks={label:function(t){return '$'+Number(t.yLabel).toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2})+' MXN';}};
    o.scales.yAxes[0].ticks.callback=function(v){return '$'+Number(v).toLocaleString('es-MX');};
  });
  chart('ivcEstadosPedidos','doughnut',['Pendientes','En preparación','Finalizados'],d.etapas||[],[palette[2],palette[0],palette[3]],function(o){delete o.scales;o.cutoutPercentage=70;o.legend={display:true,position:'bottom',labels:{fontColor:'#6b8197',boxWidth:12,padding:16}};});
  chart('ivcTopProductos','horizontalBar',d.productos||[],d.unidades||[],palette[1],function(o){
    o.scales.xAxes[0].ticks.beginAtZero=true;
    o.scales.xAxes[0].ticks.precision=0;
    o.scales.yAxes[0].gridLines.display=false;
    o.scales.yAxes[0].ticks.callback=function(v){return v.length>23?v.substr(0,22)+'…':v;};
  });
  chart('ivcCategorias','horizontalBar',d.categorias||[],d.conteoCategorias||[],palette[0],function(o){
    o.scales.xAxes[0].ticks.beginAtZero=true;
    o.scales.xAxes[0].ticks.precision=0;
    o.scales.yAxes[0].gridLines.display=false;
    o.scales.yAxes[0].ticks.callback=function(v){return v.length>23?v.substr(0,22)+'…':v;};
  });
})();
