var WEIBO_REG = /^weibo.com\/\d{10}\/\d{16}/;
var RENREN_REG = /^page.renren.com*/;
var RENREN_USER_ID_REG = /\"page_id\":\d*/;
var RENREN_STATUS_ID_REG = /\"status_id\":\d*/;
var RENREN_BLOG_ID_REG = /\"blog_id\":\d*/;
var KAIXIN_REG = /^www.kaixin001.com*/;
var KAIXIN_SOURCE_ID_REG = /objid=\d*/;
var KAIXIN_SOURCE_TYPE_REG = /objtype=\d*/;
var KAIXIN_USER_ID_REG = /ouid=\d*/;
var TENCENT_REG = /^t.qq.com\/\d{14}/;
var NETEASE_REG = /^t.163.com\/*/;
var DOUBAN_REG = /^site.douban.com*/;

Sns = function(url){
    if(typeof(url)=="string"){
		this._url= url;
		if(Sns.isWeibo(url)){
			this._type = "Weibo";
			var ids = url.split("/");
			this._sourceId = ids[2];
			this._userId = ids[1];
		}else if(Sns.isRenren(url)){
			this._type = "Renren";
			reg = RENREN_PAGE_ID_REG.exec(url);
			if(reg){
				this._userId = reg[0].split(":")[1];
			}
			reg = RENREN_STATUS_ID_REG.exec(url);
			if(reg){
				this._sourceId = reg[0].split(":")[1];
				this._sourceType = "status";
			}else{
				reg = RENREN_BLOG_ID_REG.exec(url);
				if(reg){
					this._sourceId = reg[0].split(":")[1];
					this._sourceType = "blog";
				}
			}
		}else if(Sns.isKaixin(url)){
			this._type = "Kaixin";
			reg = KAIXIN_SOURCE_ID_REG.exec(url);
			if(reg){
				this._sourceId = reg[0].split("=")[1];
			}
			reg = KAIXIN_SOURCE_TYPE_REG.exec(url);
			if(reg){
				this._sourceType = reg[0].split("=")[1];
			}
			reg = KAIXIN_USER_ID_REG.exec(url);
			if(reg){
				this._userId = reg[0].split("=")[1];
			}
		}else if(Sns.isNetease(url)){
			this._type = "Netease";
			var ids = url.split("/");
			this._sourceId = ids[2];
			this._userId = ids[1];
		}else if(Sns.isTencentWeibo){
			this._type = "Tencent";
			this._sourceId = url.substring(9);
		}
	}
};
Sns.isWeibo = function(url){
	return WEIBO_REG.test(url);
}
Sns.isRenren = function(url){
	return RENREN_REG.test(url);
}
Sns.isTencentWeibo = function(url){
	return TENCENT_REG.test(url);
}
Sns.isKaixin = function(url){
	return KAIXIN_REG.test(url);
}
Sns.isNetease = function(url){
	return NETEASE_REG.test(url);
}
Sns.isDouban = function(url){
	return DOUBAN_REG.test(url)
}
Sns.isSns = function(url){
	return Sns.isWeibo(url)||Sns.isRenren(url)||Sns.isTencentWeibo(url)||Sns.isKaixin(url)||Sns.isNetease(url)||Sns.isDouban(url);
}
Sns.getUserImage = function(imagePath,size){
	if(imagePath.indexOf("http://app.qlogo.cn/mbloghead/")==0){
		return imagePath+"/"+(size||"50");
	}else{
		return imagePath;
	}
}
Sns.str62keys = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
Sns.int10to62 = function(int10) {
    var s62 = '';
    var r = 0;
    while (int10 != 0) {
        r = int10 % 62;
        s62 = this.str62keys.charAt(r) + s62;
        int10 = Math.floor(int10 / 62);
    }
    return s62;
}
Sns.mid2url= function(mid) {
    if (typeof (mid) != 'string') return false;
    var url = '';
    for (var i = mid.length - 7; i > -7; i = i - 7)
    {
        var offset1 = i < 0 ? 0 : i;
        var offset2 = i + 7;
        var num = mid.substring(offset1, offset2);
        num = this.int10to62(num);
        url = num + url;
    }
    return url;
}
Sns.str62to10 = function(str62) {
    var i10 = 0;
    for (var i = 0; i < str62.length; i++) {
        var n = str62.length - i - 1;
        var s = str62[i];
        i10 += this.str62keys.indexOf(s) * Math.pow(62, n);
    }
    return i10;
}
Sns.url2mid = function(url) {
    var mid = '';
    for (var i = url.length - 4; i > -4; i = i - 4)
    {
        var offset1 = i < 0 ? 0 : i;
        var offset2 = i + 4;
        var str = url.substring(offset1, offset2);
        str = this.str62to10(str);
        if (offset1 > 0)
        {
            while (str.length < 7) {
                str = '0' + str;
            }
        }
        mid = str + mid;
    }
    return mid;
}
Sns.prototype = {
	_url : null,
	
	_type : null,
	
	_sourceId : null,
	
	_sourceType : null,
	
	_userId : null,
	
	getType : function(){
		return this._type;
	},

	getSourceId : function(){
		return this._sourceId;
	},
	
	getSourceType : function(){
		return this._sourceType;
	},
	
	getUserId : function(){
		return this._userId;
	},
	getSinglePage : function(){
		switch (this._type) {
		case "Weibo":
			return "http://weibo.com/"+this._userId+"/"+Sns.mid2url(this._sourceId);
		case "Tencent":
			return "http://t.qq.com/p/t/"+this._sourceId;
		case "Netease":
			return "http://t.163.com/"+this._userId+"/status/"+this._sourceId;
		case "Kaixin":
			if(this._url.indexOf("objtype=diary")>-1){
				return "http://www.kaixin001.com/diary/view.php?uid="+this._userId+"&did="+this._sourceId;
			}else{
				return "http://www.kaixin001.com/home/"+this._userId+".html";
			}
		default:
			return this._url;
		}
	},
	
}