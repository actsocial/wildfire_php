NOTE: generate static page on server on current language!

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
	delete "<!-- // <![CDATA[ -"

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