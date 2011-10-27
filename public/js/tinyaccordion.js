var TINY={};

function T$(i){return document.getElementById(i)}
function T$$(e,p){return p.getElementsByTagName(e)}

TINY.accordion=function(){
	function slider(n){this.n=n; this.a=[]}
	slider.prototype.init=function(t,e,m,o,k,b){
		var a=T$(t), i=s=0, n=a.childNodes, l=n.length, offset=b||0; this.s=k||0; this.m=m||0;
		for(i;i<l;i++){
			var v=n[i];
			//if element is li
			if (v.nodeType!=3) {
				this.a[s]={}; 
				var j=0, q=v.childNodes; 
				for(j;j<q.length;j++) {
					w=q[j];
					if (w.className=="acc_header") {
						this.a[s].h=h=w;
						//alert($(".acc_header_expand", w).html());
						$(".acc_header_expand", w).click(new Function(this.n+'.pr(0,'+s+')'));
					} else if (w.className=="acc_section") {
						this.a[s].c=c=w;
					}
				};
				// if current "s" is asked to exand, height='auto' else height=0
				if(o==s){if(this.s!=0) h.className=this.s; c.style.height='auto'; c.d=1}
				else{c.style.height=0; c.d=-1}
				s++;
			}
		}
		this.l=s-offset;
	};
	slider.prototype.pr=function(f,d){
		for(var i=0;i<this.l;i++){
			var h=this.a[i].h, c=this.a[i].c, k=c.style.height; k=k=='auto'?1:parseInt(k); clearInterval(c.t);
			if((k!=1&&c.d==-1)&&(f==1||i==d)) {
				// modified by wildfire: add header click function named "onHeaderOpen"
				if(typeof(eval(onHeaderOpen))=="function") {
					onHeaderOpen(h);
				}
				c.style.height=''; c.m=c.offsetHeight; c.style.height=k+'px'; c.d=1; if(this.s!=0) h.className=this.s; su(c,1);
			}else if(k>0&&(f==-1||this.m||i==d)){
				c.d=-1; h.className='acc_header'; su(c,-1);
				// modified by wildfire: add header click function named "onHeaderClose"
				if(typeof(eval(onHeaderClose))=="function") {
					onHeaderClose(h);
				}
			}
		}
	};
	function su(c){c.t=setInterval(function(){sl(c)},20)};
	function sl(c){
		var h=c.offsetHeight, d=c.d==1?c.m-h:h; c.style.height=h+(Math.ceil(d/5)*c.d)+'px';
		c.style.opacity=h/c.m; c.style.filter='alpha(opacity='+h*100/c.m+')';
		if((c.d==1&&h>=c.m)||(c.d!=1&&h==1)){if(c.d==1){c.style.height='auto'} clearInterval(c.t)}
	};
	return{slider:slider}
}();