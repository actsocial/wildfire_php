
在重新Checkout一个新版本后要修改的config.ini的内容如下

joomla.home = http://www.xingxinghuo.com
joomla.loginFailed = "http://www.xingxinghuo.com/index.php?option=com_content&view=article&id=92&Itemid=92"
app.home = http://home.xingxinghuo.com

添加

image.host=http://image.influencerforce.com

NOTE: generate static page on server on current language!
================================================================================

静态页面配置步骤：

1.超找 Search id="id" 使同一行中 value=""

2.确保语言是正确的
    
    如果是中文版，请将以下替换
    "Please choose" 请选择, "Others" 其他, "Submit" 提交
    'Mandatory questions must be filled out' 提交失败：必填的题目不能为空
    'Can not submit empty questionnaire form' 提交失败：不能提交空报告

3.删除 "Framework.registerAjaxSensors();"

4.口碑报告

    如果是口碑报告, 删除 "谢谢您的合作！"

5.删除所有 "<!-- // <![CDATA[ -"

6.将onsubmit="return (loading(); checkMandatory() && checkAnswerSet() && checkCommentAnswers());"中 loading(); 删除

7. 在 checkMandatory() 这个function中，将以下
   **********************************
    var buttons = ["OK"];
    var callbacks = [ function() { WebDialog.close();return false;}]; 
    WebDialog.showMessageBox(html, "center", "center", 600, buttons, callbacks);
   **********************************
   替换为
   **********************************
    jQuery("#check-alert-dialog .content").html(html);
    jQuery("#check-alert-dialog").dialog('open');

8.在 checkAnswerSet() 这个function中，将以下
   **********************************
    var buttons = ["OK"];
    var callbacks = [ function() { WebDialog.close();return false;}]; 
    WebDialog.showMessageBox(html, "center", "center", 600, buttons, callbacks);
   **********************************
   替换为
   **********************************
    jQuery("#check-alert-dialog .content").html(html);
    jQuery("#check-alert-dialog").dialog('open');
   **********************************

=================================================================================
Static Page Configure Steps:

1. delete access code.(Search id="id", make sure value="")

for cn version only
2. make sure the language is right
   e.g. if chinese version, "Please choose" 请选择, "Others" 其他, "Submit" 提交
   'Mandatory questions must be filled out' 提交失败：必填的题目不能为空
   'Can not submit empty questionnaire form' 提交失败：不能提交空报告

3. delete "Framework.registerAjaxSensors();"

for wom report only:
4. if it is wom report, delete "谢谢您的合作！"

5. For report only
	DELETE ALL "<!-- // <![CDATA[ -"

6. For report only
onsubmit="return (loading(); checkMandatory() && checkAnswerSet() && checkCommentAnswers());"
 * remove loading();
 * In checkMandatory() method
   replace
   **********************************
    var buttons = ["OK"];
    var callbacks = [ function() { WebDialog.close();return false;}]; 
    WebDialog.showMessageBox(html, "center", "center", 600, buttons, callbacks);
   **********************************
   with
   **********************************
    jQuery("#check-alert-dialog .content").html(html);
    jQuery("#check-alert-dialog").dialog('open');
   **********************************
 * In checkAnswerSet() method
   replace
   **********************************
    var buttons = ["OK"];
    var callbacks = [ function() { WebDialog.close();return false;}]; 
    WebDialog.showMessageBox(html, "center", "center", 600, buttons, callbacks);
   **********************************
   with
   **********************************
    jQuery("#check-alert-dialog .content").html(html);
    jQuery("#check-alert-dialog").dialog('open');
   **********************************