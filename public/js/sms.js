<!--
function CheckDraft(type){
	document.all.draft.value='1';
	if (type!="") document.forms[0].action = 'sms_send_finish.net';
	document.forms[0].submit();
}
function sendCheckDraft(type){
	document.all.draft.value='1';
	document .forms[0].action='sms_send_Draft.net';
	document.forms[0].submit();
}
var maxlen=60;
var atype=0;
function smsCount(frm, maxlimit) {	
	var chrlen=0;
	var newvalue = "";
	//if(atype==3){
		//newvalue = frm.Msg.value.replace(/[^\x00-\xff]/g, "**");  
		//chrlen = newvalue.length;  
	//}else chrlen = frm.Msg.value.length;
	chrlen = frm.Msg.value.length;
	if (chrlen > (maxlimit*maxlen)) {
		frm.Msg.value = frm.Msg.value.substring(0, maxlimit*maxlen);
		alert("您最多可以同时发"+maxlimit+"条短信("+(maxlimit*maxlen)+"个字符)");
	}
	//if(atype==3){
		//newvalue = frm.Msg.value.replace(/[^\x00-\xff]/g, "**");  
		//chrlen = newvalue.length;
	//}else chrlen = frm.Msg.value.length;
	chrlen = frm.Msg.value.length;
	frm.chrLen.value=chrlen;
	frm.smsLen.value=Math.ceil(chrlen/maxlen);
} 
function Trim(str){
	str=str.replace(/^ {1,}/g,"");
	str=str.replace(/ {1,}$/g,"");
	return str;
}
function CheckForm(obj){
	obj.PhoneNum.value = Trim(obj.PhoneNum.value);
	if (obj.PhoneNum.value==""){
		alert("接收号码不能为空.");
		return false;
	}
	/*LawlessChar ="洪志/法轮/宏志/真善忍/大法/fa lun/falun/发轮/发伦/发抡/发沦/发囵/发仑/发纶/法纶/法仑/法囵/法沦/法抡/法伦/功友/弟子/师傅/师父/法论/发论/法.轮.功/"+
		"法 轮 功/自焚/自焚/玄`机/现身/江独裁/江八点/江泽民/李鹏/朱容基/胡锦涛/温家宝/锦涛/十六大/共产党/政治风波/疆独/民猪/民运/ 古怪歌/推翻/示威/政变/静坐/分裂/"+
		"台*湾/吕秀莲/独立/西藏/中华民国/造反/新华内情/达赖/镇压/东突/开放/游行/上访/罢课/罢工/集会/广闻/ 打倒/压迫/反革命/疆独/无能/"+
		"教徒/人权/迫害/共产党/吕秀莲/正法/预约/日本/反日/抗日/小泉/靖国神社/日货/钓鱼岛/涉日/香港总部/主席/暴乱/窃听器/弹药/枪支/现身/六合彩/色情/嫖娼/三陪/他妈的"+
		"/龙卷风/淫秽/黄色/非典/包赢/日他/Soccer01.com/中奖/大奖/一等奖/特等奖/黑庄/13423205670/畜生/蠢猪/婊子/王八蛋/迷药/九码/六码/三码/干你娘/妓女/ fuck/强奸/"+
		"dafa/小鸡鸡/操你/鸡巴/日你妈/傻B/ SIM卡抽奖/操你娘/人民大众/时事参考/人民内情真相/新华举报/鸡毛信文汇/人民真实报道/大参考/大纪元/杂志/联总之声/传单/舆论/"+
		"美国之音/人民报讯/E周刊/博讯/人民报/中俄边界新约/国研新闻邮件/简鸿章/新闻封锁/人民大众时事参考/鸡毛信文汇/联总之声传单/九、评/九.评/九评/九-评/猛料/突厥斯坦"+
		"/印尼伊斯兰祈祷团/东突厥斯坦/伊斯兰运动/拉登/拉丹/自由运动/回民/天葬/中国移动通信/小灵通/CDMA/绿色环保手机/IP17908/语音/拨打/合约/广告/7.310/9.635/兆赫/灵动卡"+
		"/中国银联/刷卡消费/银行联合管理局";
	tmpSplit = LawlessChar.split("/");
	for (i=0;i<=tmpSplit.length-1;i++){
		if (obj.Msg.value.indexOf(tmpSplit[i]) > -1) {
			alert("您的短信内容含有非法字符 "+tmpSplit[i]);
			return false;
		}
	}*/
	obj.Msg.value = Trim(obj.Msg.value);
	if (obj.Msg.value==""){
		alert("请输入短信内容.");
		return false;
	}
	return true;
}
function selContact(idsms){
	var url = 'sms_contact_list.net?IDSMSUser='+idsms;
	var flag = window.showModalDialog(url ,"","scroll:yes;center:yes;help:no;status:no;dialogWidth:650px;dialogHeight:450px");
}
function selContact(idsms){
	var url = 'sms_send_list.net?IDSMSUser='+idsms;
	var flag = window.showModalDialog(url ,"","scroll:yes;center:yes;help:no;status:no;dialogWidth:650px;dialogHeight:450px");
}
function selContactphrase(idsms){
	var msgObj = document.getElementById("msg");
	if(msgObj.pos ==undefined)
	{
		msgObj.select();
		msgObj.pos = document.selection.createRange();
	}
	var url = 'sms_phraseSend.net?IDSMSUser='+idsms;
	var flag = window.showModalDialog(url ,"","scroll:yes;center:yes;help:no;status:no;dialogWidth:650px;dialogHeight:450px");
	if(flag ==undefined) document.getElementById("msg").pos.text ="";
	else document.getElementById("msg").pos.text = flag;
}
function selGroupMember(idsms)
{
	frm.action = "sms_send_list.net?IDSMSUser=idsms&chrAction=selGroupMember";
	frm.chrAction.value="selGroupMember";
	frm.submit();
}
function selCroup(frm){
	frm.action = 'sms_group.net?IDSMSUser=<?=$IDSMSUser?>';
	frm.submit();
}
function ResetForm(frm){
	frm.PhoneNum.value='';
	frm.smsAbout.value='';
	frm.Msg.value='';
	frm.chrLen.value=0;
	frm.smsLen.value=0;
}
function checkHour(inp){
	var hour = (new Date()).getHours();
	if(!(/^[0-9]{1,2}$/.test(inp.value)) || inp.value<0 || inp.value>23)
		inp.value=hour; 
	return true;
}
function checkMinute(inp){
	var minute = (new Date()).getMinutes();
	if(!(/^[0-9]{1,2}$/.test(inp.value)) || inp.value<0 || inp.value>59)
		inp.value=minute; 
	return true;
}
function getCurDate(date){
	//var dt = new Date();
	//var curdate = dt.getYear()+"-"+dt.getMonth()+"-"+dt.getDay();
	//var curdate=dt.toDateString();
	//return curdate;
	return date;
}
//--> 
function checkCheckBoxDis(){
	 //var subgroup=document.getElementById(id);
	 var inputs = document.getElementsByTagName("input");
	var inputsLen = inputs.length;
	var countChecked=0;
	for(var i=0;i<inputsLen;i++){
		if(inputs[i].type=="checkbox"&&inputs[i].checked) countChecked++;
	 }
	 return countChecked;
}
function groupCheck(){
	var checkBoxDis=checkCheckBoxDis();
	var clueBox1=document.getElementById('clueDiv1');
	var clueBox2=document.getElementById('clueDiv2');
	if(checkBoxDis==0){
		clueBox1.style.display="";
	}else{
		clueBox2.style.display="";
	}
}
function cancelChoice(cluename){
	var clueBox=document.getElementById(cluename);
	clueBox.style.display=(clueBox.style.display=='none')?'':'none';
}
function sureChoice(frmId){
	var frm=document.getElementById(frmId);
	frm.chrAction.value='del';
	frm.submit();
}
function checkItem(e, allName)
{
  var all = document.getElementsByName(allName)[0];
  if(!e.checked) all.checked = false;
  else
  {
    var aa = document.getElementsByName(e.name);
    for (var i=0; i<aa.length; i++)
     if(!aa[i].checked) return;
    all.checked = true;
  }
}
