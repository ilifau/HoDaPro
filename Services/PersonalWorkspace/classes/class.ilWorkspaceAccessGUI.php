<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * ACL access handler GUI
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @version $Id$
 *
 * @ilCtrl_Calls ilWorkspaceAccessGUI: ilMailSearchCoursesGUI, ilMailSearchGroupsGUI
 * @ilCtrl_Calls ilWorkspaceAccessGUI: ilMailSearchGUI, ilPublicUserProfileGUI, ilSingleUserShareGUI
 *
 * @ingroup ServicesPersonalWorkspace
 */
class ilWorkspaceAccessGUI
{
    /**
     * @var ilTabsGUI
     */
    protected $tabs;

    /**
     * @var ilTemplate
     */
    protected $tpl;

    /**
     * @var ilToolbarGUI
     */
    protected $toolbar;

    /**
     * @var ilObjUser
     */
    protected $user;

    /**
     * @var ilSetting
     */
    protected $settings;

    protected $ctrl;
    protected $lng;
    protected $node_id;
    protected $access_handler;
    protected $footer;
    protected $page_id;

    const PERMISSION_REGISTERED = -1;
    const PERMISSION_ALL_PASSWORD = -3;
    const PERMISSION_ALL = -5;

    /**
     * @var string
     */
    protected $blocking_message = "";

    public function __construct($a_node_id, $a_access_handler, $a_is_portfolio = false, $a_footer = null)
    {
        global $DIC;

        $this->tabs = $DIC->tabs();
        $this->tpl = $DIC["tpl"];
        $this->toolbar = $DIC->toolbar();
        $this->user = $DIC->user();
        $this->settings = $DIC->settings();
        $ilCtrl = $DIC->ctrl();
        $lng = $DIC->language();

        $this->ctrl = $ilCtrl;
        $this->lng = $lng;
        $this->node_id = $a_node_id;
        $this->access_handler = $a_access_handler;
        $this->is_portfolio = (bool) $a_is_portfolio;
        $this->footer = $a_footer;
        if($_REQUEST["user_page"]){
			$this->page_id = $_REQUEST["user_page"];
		}else{
			$this->page_id = $_REQUEST["ppage"];

		}
    }

    /**
     * Set blocking message
     *
     * @param string $a_val blocking message
     */
    public function setBlockingMessage($a_val)
    {
        $this->blocking_message = $a_val;
    }

    /**
     * Get blocking message
     *
     * @return string blocking message
     */
    public function getBlockingMessage()
    {
        return $this->blocking_message;
    }


    public function executeCommand()
    {
        $ilTabs = $this->tabs;
        $tpl = $this->tpl;

        $next_class = $this->ctrl->getNextClass($this);
        $cmd = $this->ctrl->getCmd();

        switch ($next_class) {
            case "ilmailsearchcoursesgui":
                $ilTabs->setBackTarget(
                    $this->lng->txt("back"),
                    $this->ctrl->getLinkTarget($this, "share")
                );
                include_once('Services/Contact/classes/class.ilMailSearchCoursesGUI.php');
                $csearch = new ilMailSearchCoursesGUI($this->access_handler, $this->node_id);
                $this->ctrl->setReturn($this, 'share');
                $this->ctrl->forwardCommand($csearch);
                
                $this->setObjectTitle();
                break;
            
            case "ilmailsearchgroupsgui":
                $ilTabs->setBackTarget(
                    $this->lng->txt("back"),
                    $this->ctrl->getLinkTarget($this, "share")
                );
                include_once('Services/Contact/classes/class.ilMailSearchGroupsGUI.php');
                $gsearch = new ilMailSearchGroupsGUI($this->access_handler, $this->node_id);
                $this->ctrl->setReturn($this, 'share');
                $this->ctrl->forwardCommand($gsearch);
                
                $this->setObjectTitle();
                break;
            
            case "ilmailsearchgui":
                $ilTabs->setBackTarget(
                    $this->lng->txt("back"),
                    $this->ctrl->getLinkTarget($this, "share")
                );
                include_once('Services/Contact/classes/class.ilMailSearchGUI.php');
                $usearch = new ilMailSearchGUI($this->access_handler, $this->node_id);
                $this->ctrl->setReturn($this, 'sharePage');
                $this->ctrl->forwardCommand($usearch);
                
                $this->setObjectTitle();
                break;

            case "ilsingleusersharegui":
                $ilTabs->setBackTarget(
                    $this->lng->txt("back"),
                    $this->ctrl->getLinkTarget($this, "share")
                );
                include_once('Services/PersonalWorkspace/classes/class.ilSingleUserShareGUI.php');
                $ushare = new ilSingleUserShareGUI($this->access_handler, $this->node_id);
                $this->ctrl->setReturn($this, 'share');
                $this->ctrl->forwardCommand($ushare);

                $this->setObjectTitle();
                break;

            case "ilpublicuserprofilegui":
                $ilTabs->clearTargets();
                $ilTabs->setBackTarget(
                    $this->lng->txt("back"),
                    $this->ctrl->getLinkTarget($this, "share")
                );

                include_once('./Services/User/classes/class.ilPublicUserProfileGUI.php');
                $prof = new ilPublicUserProfileGUI($_REQUEST["user"]);
                $prof->setBackUrl($this->ctrl->getLinkTarget($this, "share"));
                $tpl->setContent($prof->getHTML());
                break;

            default:
                // $this->prepareOutput();
                if (!$cmd) {
                    $cmd = "sharePage";
                }
                return $this->$cmd();
        }

        return true;
    }

    /**
     * restore object title
     * //fau: Einzelne Portfolio-Seiten Freigeben if parameter given, use parameter as title.
     * @return string
     */
    protected function setObjectTitle($a_title = "")
    {
        $tpl = $this->tpl;

		if($a_title){
			$tpl->setTitle($a_title);
		}else{
			if (!$this->is_portfolio) {
				$obj_id = $this->access_handler->getTree()->lookupObjectId($this->node_id);
			} else {
				$obj_id = $this->node_id;
			}
			$tpl->setTitle(ilObject::_lookupTitle($obj_id));
		}
    }

    protected function getAccessHandler()
    {
        return $this->access_handler;
    }

    protected function share()
    {
    	echo "share";exit;
    	/*
        $ilToolbar = $this->toolbar;
        $tpl = $this->tpl;
        $ilUser = $this->user;
        $ilSetting = $this->settings;


        // blocking message
        if ($this->getBlockingMessage() != "") {
            $tpl->setContent($this->getBlockingMessage());
            return;
        }

        $options = array();
        /*
        $options["user"] = $this->lng->txt("wsp_set_permission_single_user");

        include_once 'Modules/Group/classes/class.ilGroupParticipants.php';
        $grp_ids = ilGroupParticipants::_getMembershipByType($ilUser->getId(), 'grp');
        if (sizeof($grp_ids)) {
            $options["group"] = $this->lng->txt("wsp_set_permission_group");
        }

        include_once 'Modules/Course/classes/class.ilCourseParticipants.php';
        $crs_ids = ilCourseParticipants::_getMembershipByType($ilUser->getId(), 'crs');
        if (sizeof($crs_ids)) {
            $options["course"] = $this->lng->txt("wsp_set_permission_course");
        }

        if (!$this->getAccessHandler()->hasRegisteredPermission($this->node_id)) {
            $options["registered"] = $this->lng->txt("wsp_set_permission_registered");
        }


        if ($ilSetting->get("enable_global_profiles")) {
            if (!$this->getAccessHandler()->hasGlobalPasswordPermission($this->node_id)) {
                $options["password"] = $this->lng->txt("wsp_set_permission_all_password");
            }

            if (!$this->getAccessHandler()->hasGlobalPermission($this->node_id)) {
                $options["all"] = $this->lng->txt("wsp_set_permission_all");
            }
        }

		$public_users = array();
		$all_users = array();
		$all_users_data = array();
		foreach(ilObjUser::_getAllUserData(array("firstname","lastname"), 1) as $user){
			if($user["usr_id"] != "13"){
				$all_users[] = $user["usr_id"];
				$all_users_data[$user["usr_id"]] = $user["firstname"]. " ". $user["lastname"];
			}
		}
		foreach (ilObjUser::getUserSubsetByPreferenceValue($all_users, "public_profile", "y") as $u) {
			$public_users[$u] = $all_users[$u];
		}
		foreach($all_users as $user){
			if($user != "13"){
				if(array_key_exists($user, $public_users)){
					$options[$user] = "Benutzer: ". $all_users_data[$user];
				}
			}
		}

		include_once "Services/Form/classes/class.ilPropertyFormGUI.php";
        $actions = new ilSelectInputGUI("", "action");
        $actions->setOptions($options);
        $ilToolbar->addStickyItem($actions);

        $ilToolbar->setFormAction($this->ctrl->getFormAction($this));

        include_once "Services/UIComponent/Button/classes/class.ilSubmitButton.php";
        $button = ilSubmitButton::getInstance();
        $button->setCaption("add");
        $button->setCommand("addpermissionhandler");
        $ilToolbar->addStickyItem($button);

        include_once "Services/PersonalWorkspace/classes/class.ilWorkspaceAccessTableGUI.php";
        $table = new ilWorkspaceAccessTableGUI($this, "share", $this->node_id, $this->getAccessHandler());
        $tpl->setContent($table->getHTML() . $this->footer);*/
    }

    public function addPermissionHandler()
    {
    	echo "addPermissionHandler";exit;
    	/*
        switch ($_REQUEST["action"]) {
            case "user":

                include_once './Services/User/classes/class.ilUserAccountSettings.php';
                if (ilUserAccountSettings::getInstance()->isUserAccessRestricted()) {
                    $this->ctrl->redirectByClass("ilsingleusersharegui");
                } else {
                    $this->ctrl->setParameterByClass("ilmailsearchgui", "ref", "wsp");
                    $this->ctrl->redirectByClass("ilmailsearchgui");
                }

                // no break
            case "group":
                $this->ctrl->setParameterByClass("ilmailsearchgroupsgui", "ref", "wsp");
                $this->ctrl->redirectByClass("ilmailsearchgroupsgui");

                // no break
            case "course":
                $this->ctrl->setParameterByClass("ilmailsearchcoursesgui", "ref", "wsp");
                $this->ctrl->redirectByClass("ilmailsearchcoursesgui");

                // no break
            case "registered":
                $this->getAccessHandler()->addPermission($this->node_id, self::PERMISSION_REGISTERED);
                ilUtil::sendSuccess($this->lng->txt("wsp_permission_registered_info"), true);
                $this->ctrl->redirect($this, "share");

                // no break
            case "password":
                $this->showPasswordForm();
                break;

            case "all":
                $this->getAccessHandler()->addPermission($this->node_id, self::PERMISSION_ALL);
                ilUtil::sendSuccess($this->lng->txt("wsp_permission_all_info"), true);
                $this->ctrl->redirect($this, "share");
        }
    	*/
    }

    public function removePermission()
    {
        if ($_REQUEST["obj_id"]) {
            $this->getAccessHandler()->removePermission($this->node_id, (int) $_REQUEST["obj_id"]);
            ilUtil::sendSuccess($this->lng->txt("wsp_permission_removed"), true);
        }
		$this->ctrl->setParameterByClass("ilworkspaceaccessgui", "user_page", $this->page_id);
        $this->ctrl->redirect($this, "sharePage");
    }

    protected function initPasswordForm()
    {
        include_once "Services/Form/classes/class.ilPropertyFormGUI.php";
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->lng->txt("wsp_set_permission_all_password"));

        $password = new ilPasswordInputGUI($this->lng->txt("password"), "password");
        $password->setRequired(true);
        $form->addItem($password);

        $form->addCommandButton('savepasswordform', $this->lng->txt("save"));
        $form->addCommandButton('share', $this->lng->txt("cancel"));

        return $form;
    }

    protected function showPasswordForm(ilPropertyFormGUI $a_form = null)
    {
        $tpl = $this->tpl;

        if (!$a_form) {
            $a_form = $this->initPasswordForm();
        }
        $tpl->setContent($a_form->getHTML());
    }

    protected function savePasswordForm()
    {
        $form = $this->initPasswordForm();
        if ($form->checkInput()) {
            $this->getAccessHandler()->addPermission(
                $this->node_id,
                self::PERMISSION_ALL_PASSWORD,
                md5($form->getInput("password"))
            );
            ilUtil::sendSuccess($this->lng->txt("wsp_permission_all_pw_info"), true);
            $this->ctrl->redirect($this, "share");
        }

        $form->setValuesByPost();
        $this->showPasswordForm($form);
    }

    /*
     * //Fau: Einzelne Portfolio-Seiten Freigeben
     */
    public function sharePage($page = null){
		$ilToolbar = $this->toolbar;
		$tpl = $this->tpl;
		$ilUser = $this->user;
		$ilSetting = $this->settings;

		$this->prt_id = $_REQUEST["prt_id"];
		if($page){
			$this->page_id = $page;
		}else{
			if($_REQUEST["user_page"]){
				$this->page_id = $_REQUEST["user_page"];
			}else{
				$this->page_id = $_REQUEST["ppage"];
			}
			$this->setObjectTitle("Seite: ".ilPortfolioPage::lookupTitle($this->page_id));
		}

		// blocking message
		if ($this->getBlockingMessage() != "") {
			$tpl->setContent($this->getBlockingMessage());
			return;
		}

		$options = array();
		/*
		$options["user"] = $this->lng->txt("wsp_set_permission_single_user");

		include_once 'Modules/Group/classes/class.ilGroupParticipants.php';
		$grp_ids = ilGroupParticipants::_getMembershipByType($ilUser->getId(), 'grp');
		if (sizeof($grp_ids)) {
			$options["group"] = $this->lng->txt("wsp_set_permission_group");
		}

		include_once 'Modules/Course/classes/class.ilCourseParticipants.php';
		$crs_ids = ilCourseParticipants::_getMembershipByType($ilUser->getId(), 'crs');
		if (sizeof($crs_ids)) {
			$options["course"] = $this->lng->txt("wsp_set_permission_course");
		}
*/
		if (!$this->getAccessHandler()->hasRegisteredPermission($this->node_id)) {
			$options["registered"] = $this->lng->txt("wsp_set_permission_registered");
		}
		$public_users = array();
		$all_users = array();
		$all_users_data = array();
		foreach(ilObjUser::_getAllUserData(array("firstname","lastname"), 1) as $user){
			if($user["usr_id"] != "13"){
				$all_users[] = $user["usr_id"];
				$all_users_data[$user["usr_id"]] = $user["firstname"]. " ". $user["lastname"];
			}
		}
		foreach (ilObjUser::getUserSubsetByPreferenceValue($all_users, "public_profile", "y") as $u) {
			$public_users[$u] = $all_users[$u];
		}
		foreach($all_users as $user){
			if($user != "13" AND $user != $ilUser->getId()){
				if(array_key_exists($user, $public_users)){
					$options[$user] = "Benutzer: ". $all_users_data[$user];
				}
			}
		}


		include_once "Services/Form/classes/class.ilPropertyFormGUI.php";
		$actions = new ilSelectInputGUI("", "action");
		$actions->setOptions($options);
		$ilToolbar->addStickyItem($actions);
		/*
		$user_page = new ilHiddenInputGUI("user_page");
		$user_page->setValue((int) $_REQUEST["user_page"]);
		$ilToolbar->addInputItem($user_page);*/

		$this->ctrl->setParameterByClass("ilworkspaceaccessgui", "user_page", $this->page_id);
		$ilToolbar->setFormAction($this->ctrl->getFormAction($this));

		include_once "Services/UIComponent/Button/classes/class.ilSubmitButton.php";
		$button = ilSubmitButton::getInstance();
		$button->setCaption("add");
		$button->setCommand("addpagepermissionhandler");
		$ilToolbar->addStickyItem($button);

		include_once "Services/PersonalWorkspace/classes/class.ilWorkspaceAccessTableGUI.php";
		$table = new ilWorkspaceAccessTableGUI($this, "sharePage", $this->prt_id, $this->getAccessHandler(), $this->page_id);
		$table->importData();
		$tpl->setContent($table->getHTML() . $this->footer);
    }

	public function addPagePermissionHandler()
	{
		if($_REQUEST["user_page"]){
			$this->page_id = (int) $_REQUEST["user_page"];
		}elseif($_REQUEST["ppage"]){
			$this->page_id = (int) $_REQUEST["ppage"];
		}
		if(is_int((int) $_REQUEST["action"])){
			$user =ilObjUser::_lookupName((int) $_REQUEST["action"]);
			if($user["user_id"] != 0){
				$this->access_handler->addPermission((int) $_REQUEST["prt_id"], (int) $user["user_id"], $this->page_id);
				ilUtil::sendSuccess("Seite ".ilPortfolioPage::lookupTitle($this->page_id). " ist für Benutzer ". $user["firstname"]. " ". $user["lastname"] . " freigegeben.", true);
				$this->ctrl->setParameterByClass("ilworkspaceaccessgui", "user_page", $this->page_id);
				$this->ctrl->redirect($this, "sharePage");
			}
		}


		switch ($_REQUEST["action"]) {
			case "user":
				include_once './Services/User/classes/class.ilUserAccountSettings.php';
				if (ilUserAccountSettings::getInstance()->isUserAccessRestricted()) {
					$this->ctrl->redirectByClass("ilsingleusersharegui");
				} else {
					$this->ctrl->setParameterByClass("ilmailsearchgui", "ref", "wsp");
					$this->ctrl->redirectByClass("ilmailsearchgui");
				}
			// no break
			case "registered":
				if($_REQUEST["ppage"]){
					$this->page_id = $_REQUEST["ppage"];
				}else{
					$this->page_id = $_REQUEST["user_page"];
				}
				$this->getAccessHandler()->addPermission($this->node_id, self::PERMISSION_REGISTERED, $this->page_id);
				ilUtil::sendSuccess($this->lng->txt("wsp_permission_registered_info"), true);
				$this->ctrl->setParameterByClass("ilworkspaceaccessgui", "user_page", $this->page_id);
				$this->ctrl->redirect($this, "sharePage");
		}
	}

	public function requestFeedback(){
    	if((int) $_REQUEST["obj_id"] != -1){
			$this->page_id = (int)$_REQUEST["user_page"];
			$url = ilLink::_getStaticLink((int)$_REQUEST["prt_id"], "prtf", true, "_".$this->page_id);
			$text = "Liebe/r ".$this->user->getLoginByUserId((int) $_REQUEST["obj_id"])." ,  
						hiermit erbitte ich dein Feedback zu meiner meiner Portfolioarbeit. 
						die freigegebene Portfolioseite ist unter folgendem Link erreichbar:".$url."
Bitte trage deine Rückmeldung dort im Kommentarfeld ein.
Herzlichen Dank!
Freundliche Grüße
";
			$mail = new ilMail($this->user->getId());
			if(!empty($mail->sendMail($this->user->getLoginByUserId((int) $_REQUEST["obj_id"]),"","","Feedback-Anfrage",$text,array(),true))){
				ilUtil::sendInfo("Bitte wählen Sie nur ein Benutzer um feedback anzubieten.", true);
			}else{
				ilUtil::sendSuccess("Feedback erbeten/angefragt.", true);
			}
		}
		$this->ctrl->setParameterByClass("ilworkspaceaccessgui", "user_page", $this->page_id);
		$this->ctrl->redirect($this, "sharePage");
	}
}

