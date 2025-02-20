<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Portfolio view gui base class
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 */
abstract class ilObjPortfolioBaseGUI extends ilObject2GUI
{
    /**
     * @var ilHelpGUI
     */
    protected $help;

    /**
     * @var ilMainMenuGUI
     */
    protected $main_menu;

    protected $user_id; // [int]
    protected $additional = array();
    protected $perma_link; // [string]
    protected $page_id; // [int]
    protected $page_mode; // [string] preview|edit
    
    public function __construct($a_id = 0, $a_id_type = self::REPOSITORY_NODE_ID, $a_parent_node_id = 0)
    {
        global $DIC;

        $this->user = $DIC->user();
        $this->locator = $DIC["ilLocator"];
        $this->toolbar = $DIC->toolbar();
        $this->settings = $DIC->settings();
        $this->tree = $DIC->repositoryTree();
        $this->help = $DIC["ilHelp"];
        $this->main_menu = $DIC["ilMainMenu"];
        $this->tpl = $DIC["tpl"];
        $ilUser = $DIC->user();
        $this->ui = $DIC->ui();
        
        parent::__construct($a_id, $a_id_type, $a_parent_node_id);

        $this->user_id = $ilUser->getId();
        
        $this->lng->loadLanguageModule("prtf");
        $this->lng->loadLanguageModule("user");
        $this->lng->loadLanguageModule("obj");
    }
    
    protected function addLocatorItems()
    {
        $ilLocator = $this->locator;
        
        if ($this->object) {
            $ilLocator->addItem(
                strip_tags($this->object->getTitle()),
                $this->ctrl->getLinkTarget($this, "view")
            );
        }
                
        if ($this->page_id) {
            $page = $this->getPageInstance($this->page_id);
            $title = $page->getTitle();
            if ($page->getType() == ilPortfolioPage::TYPE_BLOG) {
                $title = ilObject::_lookupTitle($title);
            }
            $this->ctrl->setParameterByClass($this->getPageGUIClassName(), "ppage", $this->page_id);
            $ilLocator->addItem(
                $title,
                $this->ctrl->getLinkTargetByClass($this->getPageGUIClassName(), "edit")
            );
        }
    }
    
    protected function determinePageCall()
    {
        // edit
        if (isset($_REQUEST["ppage"])) {
            if (!$this->checkPermissionBool("write")) {
                $this->ctrl->redirect($this, "view");
            }
            
            $this->page_id = $_REQUEST["ppage"];
            $this->page_mode = "edit";
            $this->ctrl->setParameter($this, "ppage", $this->page_id);
            return true;
        }
        // preview
        else {
            $this->page_id = $_REQUEST["user_page"];
            $this->page_mode = "preview";
            $this->ctrl->setParameter($this, "user_page", $this->page_id);
            return false;
        }
    }
    
    protected function handlePageCall($a_cmd)
    {
        $this->tabs_gui->clearTargets();
        $this->tabs_gui->setBackTarget(
            $this->lng->txt("back"),
            $this->ctrl->getLinkTarget($this, "view")
        );
        
        if (!$this->page_id) {
            $this->ctrl->redirect($this, "view");
        }

        $page_gui = $this->getPageGUIInstance($this->page_id);
        
        // needed for editor
        $page_gui->setStyleId(ilObjStyleSheet::getEffectiveContentStyleId(
            $this->object->getStyleSheetId(),
            $this->getType()
        ));
        
        $ret = $this->ctrl->forwardCommand($page_gui);

        if ($ret != "" && $ret !== true) {
            // preview (fullscreen)
            if ($this->page_mode == "preview") {
                // embedded call which did not generate any output (e.g. calendar month navigation)
                if ($ret != ilPortfolioPageGUI::EMBEDDED_NO_OUTPUT) {
                    // suppress (portfolio) notes for blog postings
                    $this->preview(false, $ret, ($a_cmd != "previewEmbedded"));
                } else {
                    $this->preview(false);
                }
            }
            // edit
            else {
                $this->setContentStyleSheet();
                if (is_string($ret)) {
                    $this->tpl->setContent($ret);
                }
            }
        }
    }
    
    /**
    * Set Additonal Information (used in public profile?)
    *
    * @param	array	$a_additional	Additonal Information
    */
    public function setAdditional($a_additional)
    {
        $this->additional = $a_additional;
    }

    /**
    * Get Additonal Information.
    *
    * @return	array	Additonal Information
    */
    public function getAdditional()
    {
        return $this->additional;
    }
        
    /**
     * Set custom perma link (used in public profile?)
     *
     * @param int $a_obj_id
     * @param string $a_type
     */
    public function setPermaLink($a_obj_id, $a_type)
    {
        $this->perma_link = array("obj_id" => $a_obj_id, "type" => $a_type);
    }
        
    
    //
    // CREATE/EDIT
    //
    
    protected function setSettingsSubTabs($a_active)
    {
        // #17455
        $this->lng->loadLanguageModule($this->getType());
        
        // general properties
        $this->tabs_gui->addSubTab(
            "properties",
            $this->lng->txt($this->getType() . "_properties"),
            $this->ctrl->getLinkTarget($this, 'edit')
        );
        
        $this->tabs_gui->addSubTab(
            "style",
            $this->lng->txt("obj_sty"),
            $this->ctrl->getLinkTarget($this, 'editStyleProperties')
        );
        
        $this->tabs_gui->activateSubTab($a_active);
    }
        
    protected function initEditCustomForm(ilPropertyFormGUI $a_form)
    {
        $this->setSettingsSubTabs("properties");
        

        // profile picture
        $ppic = new ilCheckboxInputGUI($this->lng->txt("prtf_profile_picture"), "ppic");
        $a_form->addItem($ppic);

        $prfa_set = new ilSetting("prfa");
        if ($prfa_set->get("banner")) {
            ilFileInputGUI::setPersonalWorkspaceQuotaCheck(true);

            $dimensions = " (" . $prfa_set->get("banner_width") . "x" .
                $prfa_set->get("banner_height") . ")";

            $img = new ilImageFileInputGUI($this->lng->txt("prtf_banner") . $dimensions, "banner");
            $a_form->addItem($img);

            // show existing file
            $file = $this->object->getImageFullPath(true);
            if ($file) {
                $img->setImage($file);
            }
        }

        $section = new ilFormSectionHeaderGUI();
        $section->setTitle("Feedback Einstellungen");
        $a_form->addItem($section);

        // comments
        $comments = new ilCheckboxInputGUI("privates Feedback zulassen", "comments");
        $a_form->addItem($comments);

        /* #15000
        $bg_color = new ilColorPickerInputGUI($this->lng->txt("prtf_background_color"), "bg_color");
        $a_form->addItem($bg_color);

        $font_color = new ilColorPickerInputGUI($this->lng->txt("prtf_font_color"), "font_color");
        $a_form->addItem($font_color);
        */
    }
    
    protected function getEditFormCustomValues(array &$a_values)
    {
        $a_values["comments"] = $this->object->hasPublicComments();
        $a_values["ppic"] = $this->object->hasProfilePicture();
        /*
        $a_values["bg_color"] = $this->object->getBackgroundColor();
        $a_values["font_color"] = $this->object->getFontColor();
        */
    }
    
    public function updateCustom(ilPropertyFormGUI $a_form)
    {
        $this->object->setPublicComments($a_form->getInput("comments"));
        $this->object->setProfilePicture($a_form->getInput("ppic"));
        /*
        $this->object->setBackgroundColor($a_form->getInput("bg_color"));
        $this->object->setFontcolor($a_form->getInput("font_color"));
        */
        
        $prfa_set = new ilSetting("prfa");

        if ($_FILES["banner"]["tmp_name"]) {
            $this->object->uploadImage($_FILES["banner"]);
        } elseif ($prfa_set->get('banner') and $a_form->getItemByPostVar("banner")->getDeletionFlag()) {
            $this->object->deleteImage();
        }
    }
    
    
    //
    // PAGES
    //
    
    abstract protected function getPageInstance($a_page_id = null, $a_portfolio_id = null);
    
    abstract protected function getPageGUIInstance($a_page_id);
    
    abstract public function getPageGUIClassName();
        
    /**
     * Show list of portfolio pages
     */
    public function view()
    {
        $ilToolbar = $this->toolbar;
        $ilSetting = $this->settings;
        $tree = $this->tree;
        
        if (!$this->checkPermissionBool("write")) {
            $this->ctrl->redirect($this, "infoScreen");
        }
        
        $this->tabs_gui->activateTab("pages");
        

        $button = ilLinkButton::getInstance();
        $button->setCaption("prtf_add_page");
        $button->setUrl($this->ctrl->getLinkTarget($this, "addPage"));
        $ilToolbar->addStickyItem($button);

        /*
        if (!$ilSetting->get('disable_wsp_blogs')) {
            $button = ilLinkButton::getInstance();
            $button->setCaption("prtf_add_blog");
            $button->setUrl($this->ctrl->getLinkTarget($this, "addBlog"));
            $ilToolbar->addStickyItem($button);
        }*/


        // #16571
		/*
        if ($this->getType() == "prtf") {
            $ilToolbar->addSeparator();

            $button = ilLinkButton::getInstance();
            $button->setCaption("export_html");
            $button->setUrl($this->ctrl->getLinkTarget($this, "export"));
            $ilToolbar->addButtonInstance($button);

            $button = ilLinkButton::getInstance();
            $button->setCaption("prtf_pdf");
            $button->setUrl($this->ctrl->getLinkTarget($this, "exportPDFSelection"));
            $ilToolbar->addButtonInstance($button);
        }*/
        
        $table = new ilPortfolioPageTableGUI($this, "view");
        

        $this->tpl->setContent($message . $table->getHTML());
    }
    
    /**
     * Show portfolio page creation form
     */
    protected function addPage()
    {
        $ilHelp = $this->help;

        $this->tabs_gui->clearTargets();
        $this->tabs_gui->setBackTarget(
            $this->lng->txt("back"),
            $this->ctrl->getLinkTarget($this, "view")
        );

        $ilHelp->setScreenIdComponent("prtf");
        $ilHelp->setScreenId("add_page");


        $form = $this->initPageForm("create");
        $this->tpl->setContent($form->getHTML());
    }

    /**
     * Init portfolio page form
     *
     * @param string $a_mode
     * @return ilPropertyFormGUI
     */
    public function initPageForm($a_mode = "create")
    {
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));

		if($_REQUEST["cmd"] == "addPage" AND $_REQUEST["cmdClass"] == "ilobjportfoliogui") {
			// title
			$ti = new ilHiddenInputGUI("title");
			$ti->setValue("placeholder");
			$form->addItem($ti);
		}else{
			// title
			$ti = new ilTextInputGUI($this->lng->txt("title"), "title");
			$ti->setMaxLength(200);
			$ti->setRequired(true);
			$form->addItem($ti);
		}

        // save and cancel commands
        if ($a_mode == "create") {
        	if($_REQUEST["cmdClass"] != "ilobjportfoliotemplategui"){
				$templates = ilObjPortfolioTemplate::getAvailablePortfolioTemplates();
				if (!sizeof($templates)) {

				} else {
					$tmpl = new ilSelectInputGUI($this->lng->txt("obj_prtt"), "prtt");
					$tmpl->setRequired(true);
					$tmpl->setOptions(array("" => $this->lng->txt("please_select")) + $templates);
					$form->addItem($tmpl);
				}
			}
            
            $form->setTitle($this->lng->txt("prtf_add_page") . ": " .
                $this->object->getTitle());
            $form->addCommandButton("savePage", $this->lng->txt("save"));
            $form->addCommandButton("view", $this->lng->txt("cancel"));
        } else {
            /* edit is done directly in table gui
            $form->setTitle($this->lng->txt("prtf_edit_page"));
            $form->addCommandButton("updatePage", $this->lng->txt("save"));
            $form->addCommandButton("view", $this->lng->txt("cancel"));
            */
        }
        
        return $form;
    }
        
    /**
     * Create new portfolio page
     */
    public function savePage()
    {
        $form = $this->initPageForm("create");
        if ($form->checkInput() && $this->checkPermissionBool("write")) {

			if($form->getInput("prtt")) {
				$info = explode("_", $form->getInput("prtt"));
				$page_id = $info[1];
				$portfolio_id = $info[0];

				$source = new ilObjPortfolioTemplate($portfolio_id, false);
				$source->clonePagesAndSettings($source, $this->object, array(), false, $page_id);

				// link portfolio to exercise assignment
				$this->linkPortfolioToAssignment($this->object->getId());
			}else{
				$page = $this->getPageInstance();
				$page->setType(ilPortfolioPage::TYPE_PAGE);
				$page->setTitle($form->getInput("title"));
				// use template as basis
				$layout_id = $form->getInput("tmpl");
				if ($layout_id) {
					$layout_obj = new ilPageLayout($layout_id);
					$page->setXMLContent($layout_obj->getXMLContent());
				}

				$page->create();
			}
            


            ilUtil::sendSuccess($this->lng->txt("prtf_page_created"), true);
            $this->ctrl->redirect($this, "view");
        }

        $this->tabs_gui->clearTargets();
        $this->tabs_gui->setBackTarget(
            $this->lng->txt("back"),
            $this->ctrl->getLinkTarget($this, "view")
        );

        $form->setValuesByPost();
        $this->tpl->setContent($form->getHtml());
    }
    
    /**
     * Show portfolio blog page creation form
     */
    protected function addBlog()
    {
        $ilHelp = $this->help;

        $this->tabs_gui->clearTargets();
        $this->tabs_gui->setBackTarget(
            $this->lng->txt("back"),
            $this->ctrl->getLinkTarget($this, "view")
        );

        $ilHelp->setScreenIdComponent("prtf");
        $ilHelp->setScreenId("add_blog");

        $form = $this->initBlogForm();
        $this->tpl->setContent($form->getHTML());
    }
    
    abstract protected function initBlogForm();
    
    abstract protected function saveBlog();
    
    /**
     * Save ordering of portfolio pages
     */
    public function savePortfolioPagesOrdering()
    {
        if (!$this->checkPermissionBool("write")) {
            return;
        }

        $title_changes = array();

        if (is_array($_POST["order"])) {
            foreach ($_POST["order"] as $k => $v) {
                $page = $this->getPageInstance(ilUtil::stripSlashes($k));
                if ($_POST["title"][$k]) {
                    $new_title = trim(ilUtil::stripSlashes($_POST["title"][$k]));
                    if ($page->getTitle() != $new_title) {
                        $title_changes[$page->getId()] = array("old" => $page->getTitle(), "new" => $new_title);
                        $page->setTitle($new_title);
                    }
                }
                $page->setOrderNr(ilUtil::stripSlashes($v));
                $page->update();
            }
            ilPortfolioPage::fixOrdering($this->object->getId());
        }

        $this->object->fixLinksOnTitleChange($title_changes);

        ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
        $this->ctrl->redirect($this, "view");
    }

    /**
     * Confirm portfolio deletion
     */
    public function confirmPortfolioPageDeletion()
    {
        $prtf_pages = $_REQUEST["prtf_pages"];

        if (!is_array($prtf_pages) || count($prtf_pages) == 0) {
            ilUtil::sendInfo($this->lng->txt("no_checkbox"), true);
            $this->ctrl->redirect($this, "view");
        } else {
            $this->tabs_gui->activateTab("pages");
            
            $cgui = new ilConfirmationGUI();
            $cgui->setFormAction($this->ctrl->getFormAction($this));
            $cgui->setHeaderText($this->lng->txt("prtf_sure_delete_portfolio_pages"));
            $cgui->setCancel($this->lng->txt("cancel"), "view");
            $cgui->setConfirm($this->lng->txt("delete"), "deletePortfolioPages");

            foreach ($prtf_pages as $id) {
                $page = $this->getPageInstance((int) $id);
                if ($page->getPortfolioId() != $this->object->getId()) {
                    continue;
                }

                $title = $page->getTitle();
                if ($page->getType() == ilPortfolioPage::TYPE_BLOG) {
                    $title = $this->lng->txt("obj_blog") . ": " . ilObject::_lookupTitle((int) $title);
                }
                $cgui->addItem("prtf_pages[]", $id, $title);
            }

            $this->tpl->setContent($cgui->getHTML());
        }
    }

    /**
     * Delete portfolio pages
     */
    public function deletePortfolioPages()
    {
        if (!$this->checkPermissionBool("write")) {
            return;
        }

        if (is_array($_POST["prtf_pages"])) {
            foreach ($_POST["prtf_pages"] as $id) {
                $page = $this->getPageInstance($id);
                $page->delete();
            }
        }
        ilUtil::sendSuccess($this->lng->txt("prtf_portfolio_page_deleted"), true);
        $this->ctrl->redirect($this, "view");
    }
    
    /**
     * Show user page
     */
    public function preview($a_return = false, $a_content = false, $a_show_notes = true)
    {
        $ilSetting = $this->settings;
        $ilUser = $this->user;
        
        $portfolio_id = $this->object->getId();
        $user_id = $this->object->getOwner();
        
        $this->tabs_gui->clearTargets();
            
        $pages = ilPortfolioPage::getAllPortfolioPages($portfolio_id);
        $current_page = (int) $_GET["user_page"];
        
        // validate current page
        if ($pages && $current_page) {
            $found = false;
            foreach ($pages as $page) {
                if ($page["id"] == $current_page) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $current_page = null;
            }
        }

        // display first page of portfolio if none given
        if (!$current_page && $pages) {
            $current_page = $pages;
            $current_page = array_shift($current_page);
            $current_page = $current_page["id"];
        }
        
        // #13788 - keep page after login
        if ($this->user_id == ANONYMOUS_USER_ID &&
            $this->getType() == "prtf") {
            $this->tpl->setLoginTargetPar("prtf_" . $this->object->getId() . "_" . $current_page);
        }
        
        $back_caption = "";
                        
        // public profile
        if ($_REQUEST["back_url"]) {
            $back = $_REQUEST["back_url"];
        } elseif ($_GET["baseClass"] != "ilPublicUserProfileGUI" &&
            $this->user_id && $this->user_id != ANONYMOUS_USER_ID) {
            if (!$this->checkPermissionBool("write")) {
                // shared
                if ($this->getType() == "prtf") {
                    $this->ctrl->setParameterByClass("ilportfoliorepositorygui", "shr_id", $this->object->getOwner());
                    $back = $this->ctrl->getLinkTargetByClass(array("ildashboardgui", "ilportfoliorepositorygui"), "showOther");
                    $this->ctrl->setParameterByClass("ilportfoliorepositorygui", "shr_id", "");
                }
                // listgui / parent container
                else {
                    // #12819
                    $tree = $this->tree;
                    $parent_id = $tree->getParentId($this->node_id);
                    $back = ilLink::_getStaticLink($parent_id);
                }
            }
            // owner
            else {
                $back = $this->ctrl->getLinkTarget($this, "view");
                if ($this->getType() == "prtf") {
                    $back_caption = $this->lng->txt("prtf_back_to_portfolio_owner");
                } else {
                    // #19316
                    $this->lng->loadLanguageModule("prtt");
                    $back_caption = $this->lng->txt("prtt_edit");
                }
            }
        }
        
        $ilMainMenu = $this->main_menu;
        $ilMainMenu->setMode(ilMainMenuGUI::MODE_TOPBAR_ONLY);
        if ($back) {
            // might already be set in ilPublicUserProfileGUI
            $ilMainMenu->setTopBarBack($back, $back_caption);
        }
        
        // render tabs
        $current_blog = null;
        if (count($pages) > 1) {
			$shared_page = (int) ilPortfolioAccessHandler::getExtendedData($portfolio_id);

			if($shared_page != null AND $_REQUEST["baseClass"] == "ilsharedresourceGUI"){
				$this->ctrl->setParameter($this, "user_page", $shared_page);
				$this->tabs_gui->addTab(
					"user_page_" . $shared_page,
					ilPortfolioPage::lookupTitle($shared_page),
					$this->ctrl->getLinkTarget($this, "preview")
				);
			}else{
				foreach ($pages as $p) {
					if ($p["type"] == ilPortfolioPage::TYPE_BLOG) {
						// needed for blog comments (see below)
						if ($p["id"] == $current_page) {
							$current_blog = (int) $p["title"];
						}
						$p["title"] = ilObjBlog::_lookupTitle($p["title"]);
					}

					$this->ctrl->setParameter($this, "user_page", $p["id"]);
					$this->tabs_gui->addTab(
						"user_page_" . $p["id"],
						$p["title"],
						$this->ctrl->getLinkTarget($this, "preview")
					);
				}
			}

            
            $this->tabs_gui->activateTab("user_page_" . $current_page);
        }
        
        $this->ctrl->setParameter($this, "user_page", $current_page);
        
        if (!$a_content) {
            // #18291
            if ($current_page) {
                // get current page content
                $page_gui = $this->getPageGUIInstance($current_page);
                $page_gui->setEmbedded(true);

                $content = $this->ctrl->getHTML($page_gui);
            }
        } else {
            $content = $a_content;
        }
        
        if ($a_return && $this->checkPermissionBool("write")) {
            return $content;
        }
                        
        // blog posting comments are handled within the blog
        $notes = "";
        if ($a_show_notes && $this->object->hasPublicComments() && !$current_blog && $current_page) {
            $note_gui = new ilNoteGUI($portfolio_id, $current_page, "pfpg");
            $note_gui->setRepositoryMode(false);
            $note_gui->enablePublicNotes(true);
            $note_gui->enablePrivateNotes(false);
            
            $note_gui->enablePublicNotesDeletion(($this->user_id == $user_id) &&
                $ilSetting->get("comments_del_tutor", 1));
                        
            $next_class = $this->ctrl->getNextClass($this);
            if ($next_class == "ilnotegui") {
                $notes = $this->ctrl->forwardCommand($note_gui);
            } else {
                $notes = $note_gui->getNotesHTML();
            }
        }
            
        if ($this->perma_link === null) {
            if ($this->getType() == "prtf") {
                $this->tpl->setPermanentLink($this->getType(), $this->object->getId(), "_" . $current_page);
            } else {
                $this->tpl->setPermanentLink($this->getType(), $this->object->getRefId());
            }
        } else {
            $this->tpl->setPermanentLink($this->perma_link["type"], $this->perma_link["obj_id"]);
        }
        
        // #18208 - see ilPortfolioTemplatePageGUI::getPageContentUserId()
        if ($this->getType() == "prtt" && !$this->checkPermissionBool("write")) {
            $user_id = $ilUser->getId();
        }
        
        self::renderFullscreenHeader($this->object, $this->tpl, $user_id);
        
        // #13564
        $this->ctrl->setParameter($this, "user_page", "");
        //$this->tpl->setTitleUrl($this->ctrl->getLinkTarget($this, "preview"));
        $this->ctrl->setParameter($this, "user_page", $this->page_id);
        
        // blog pages do their own (page) style handling
        if (!$current_blog) {
            $content = '<div id="ilCOPageContent" class="ilc_page_cont_PageContainer">' .
                '<div class="ilc_page_Page">' .
                    $content .
                '</div></div>';
                                        
            $this->setContentStyleSheet($this->tpl);
        }

        $this->showEditButton($current_page);

        // #10717
        $this->tpl->setContent($content .
            '<div class="ilClearFloat">' . $notes . '</div>');
    }


    /**
     * Show edit button
     */
    protected function showEditButton($page_id)
    {
        if ($page_id == 0) {
            return;
        }
        $page_class = ($this->getType() == "prtt")
            ? "ilPortfolioTemplatePageGUI"
            : "ilportfoliopagegui";
        if (ilPortfolioPage::lookupType($page_id) == ilPortfolioPage::TYPE_PAGE) {
            $this->ctrl->setParameterByClass($page_class, "ppage", $page_id);
            $button = $this->ui->factory()->button()->standard(
                $this->lng->txt("edit"),
                $this->ctrl->getLinkTargetByClass($page_class, "edit")
            );
        } else {
            $this->ctrl->setParameterByClass("ilobjbloggui", "ppage", $page_id);
            $this->ctrl->setParameterByClass("ilobjbloggui", "prt_id", (int) $_GET["prt_id"]);
            $button = $this->ui->factory()->button()->standard(
                $this->lng->txt("edit"),
                $this->ctrl->getLinkTargetByClass([$page_class, "ilobjbloggui"], "render")
            );
        }
        if ($this->checkPermissionBool("write")) {
            $this->tpl->setHeaderActionMenu($this->ui->renderer()->render($button));
        }
    }

    /**
     * Render banner, user name
     *
     * @param object  $a_tpl
     * @param int $a_user_id
     * @param bool $a_export_path
     */
    public static function renderFullscreenHeader($a_portfolio, $a_tpl, $a_user_id, $a_export = false)
    {
        global $DIC;

        $ilUser = $DIC->user();
        
        if (!$a_export) {
            ilChangeEvent::_recordReadEvent(
                $a_portfolio->getType(),
                ($a_portfolio->getType() == "prtt")
                    ? $a_portfolio->getRefId()
                    : $a_portfolio->getId(),
                $a_portfolio->getId(),
                $ilUser->getId()
            );
        }
        
        $name = ilObjUser::_lookupName($a_user_id);
        $name = $name["lastname"] . ", " . ($t = $name["title"] ? $t . " " : "") . $name["firstname"];
        
        // show banner?
        $banner = $banner_width = $banner_height = false;
        $prfa_set = new ilSetting("prfa");
        if ($prfa_set->get("banner")) {
            $banner = ilWACSignedPath::signFile($a_portfolio->getImageFullPath());
            $banner_width = $prfa_set->get("banner_width");
            $banner_height = $prfa_set->get("banner_height");
            if ($a_export) {
                $banner = basename($banner);
            }
        }
        
        // profile picture
        $ppic = null;
        if ($a_portfolio->hasProfilePicture()) {
            $ppic = ilObjUser::_getPersonalPicturePath($a_user_id, "xsmall", true, true);
            if ($a_export) {
                $ppic = basename($ppic);
            }
        }
        
        $a_tpl->resetHeaderBlock(false);
        // $a_tpl->setBackgroundColor($a_portfolio->getBackgroundColor());
        // @todo fix this
        $a_tpl->setBanner($banner, $banner_width, $banner_height, $a_export);
        $a_tpl->setTitleIcon($ppic);
        $a_tpl->setTitle($a_portfolio->getTitle());
        // $a_tpl->setTitleColor($a_portfolio->getFontColor());
        $a_tpl->setDescription($name);
        
        // to get rid of locator in portfolio template preview
        $a_tpl->setVariable("LOCATOR", "");
        
        // :TODO: obsolete?
        // $a_tpl->setBodyClass("std ilExternal ilPortfolio");
    }
            
    public function export()
    {
        $port_export = new \ILIAS\Portfolio\Export\PortfolioHtmlExport($this);
        $zip = $port_export->exportHtml();

        ilUtil::deliverFile($zip, $this->object->getTitle() . ".zip", '', false, true);
    }
    
    
    /**
     * Select target portfolio for page(s) copy
     */
    public function copyPageForm($a_form = null)
    {
        $prtf_pages = $_REQUEST["prtf_pages"];

        if (!is_array($prtf_pages) || count($prtf_pages) == 0) {
            ilUtil::sendInfo($this->lng->txt("no_checkbox"), true);
            $this->ctrl->redirect($this, "view");
        } else {
            $this->tabs_gui->activateTab("pages");
            
            if (!$a_form) {
                $a_form = $this->initCopyPageForm();
            }
        
            foreach ($prtf_pages as $page_id) {
                $item = new ilHiddenInputGUI("prtf_pages[]");
                $item->setValue((int) $page_id);
                $a_form->addItem($item);
            }
            
            $this->tpl->setContent($a_form->getHTML());
        }
    }
    
    public function copyPage()
    {
        $form = $this->initCopyPageForm();
        if ($form->checkInput()) {
            // existing
            if ($form->getInput("target") == "old") {
                $portfolio_id = $form->getInput("prtf");
            }
            // new
            else {
                $portfolio = new ilObjPortfolio();
                $portfolio->setTitle($form->getInput("title"));
                $portfolio->create();
                $portfolio_id = $portfolio->getId();
            }
            
            // copy page(s)
            foreach ($_POST["prtf_pages"] as $page_id) {
                $source = $this->getPageInstance($page_id);
                $target = $this->getPageInstance(null, $portfolio_id);
                $target->setXMLContent($source->copyXmlContent(true)); // copy mobs
                $target->setType($source->getType());
                $target->setTitle($source->getTitle());
                $target->create();
            }
                
            ilUtil::sendSuccess($this->lng->txt("prtf_pages_copied"), true);
            $this->ctrl->redirect($this, "view");
        }
        
        $form->setValuesByPost();
        $this->copyPageForm($form);
    }
    
    abstract protected function initCopyPageFormOptions(ilPropertyFormGUI $a_form);
    
    public function initCopyPageForm()
    {
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->lng->txt("prtf_copy_page"));
        
        $this->initCopyPageFormOptions($form);

        $form->addCommandButton("copyPage", $this->lng->txt("save"));
        $form->addCommandButton("view", $this->lng->txt("cancel"));
        
        return $form;
    }
    
    
    ////
    //// Style related functions
    ////
    
    public function setContentStyleSheet($a_tpl = null)
    {
        $tpl = $this->tpl;

        if ($a_tpl != null) {
            $ctpl = $a_tpl;
        } else {
            $ctpl = $tpl;
        }

        $ctpl->setCurrentBlock("ContentStyle");
        $ctpl->setVariable(
            "LOCATION_CONTENT_STYLESHEET",
            ilObjStyleSheet::getContentStylePath($this->object->getStyleSheetId())
        );
        $ctpl->parseCurrentBlock();
    }
    
    public function editStyleProperties()
    {
        $this->checkPermission("write");
        
        $this->tabs_gui->activateTab("settings");
        $this->setSettingsSubTabs("style");
        
        $form = $this->initStylePropertiesForm();
        $this->tpl->setContent($form->getHTML());
    }
    
    public function initStylePropertiesForm()
    {
        $ilSetting = $this->settings;
                        
        $this->lng->loadLanguageModule("style");

        $form = new ilPropertyFormGUI();
        
        $fixed_style = $ilSetting->get("fixed_content_style_id");
        $style_id = $this->object->getStyleSheetId();

        if ($fixed_style > 0) {
            $st = new ilNonEditableValueGUI($this->lng->txt("style_current_style"));
            $st->setValue(ilObject::_lookupTitle($fixed_style) . " (" .
                $this->lng->txt("global_fixed") . ")");
            $form->addItem($st);
        } else {
            $st_styles = ilObjStyleSheet::_getStandardStyles(
                true,
                false,
                $_GET["ref_id"]
            );

            $st_styles[0] = $this->lng->txt("default");
            ksort($st_styles);

            if ($style_id > 0) {
                // individual style
                if (!ilObjStyleSheet::_lookupStandard($style_id)) {
                    $st = new ilNonEditableValueGUI($this->lng->txt("style_current_style"));
                    $st->setValue(ilObject::_lookupTitle($style_id));
                    $form->addItem($st);

                    // delete command
                    $form->addCommandButton("editStyle", $this->lng->txt("style_edit_style"));
                    $form->addCommandButton("deleteStyle", $this->lng->txt("style_delete_style"));
                }
            }

            if ($style_id <= 0 || ilObjStyleSheet::_lookupStandard($style_id)) {
                $style_sel = new ilSelectInputGUI(
                    $this->lng->txt("style_current_style"),
                    "style_id"
                );
                $style_sel->setOptions($st_styles);
                $style_sel->setValue($style_id);
                $form->addItem($style_sel);

                $form->addCommandButton("saveStyleSettings", $this->lng->txt("save"));
                $form->addCommandButton("createStyle", $this->lng->txt("sty_create_ind_style"));
            }
        }
        
        $form->setTitle($this->lng->txt($this->getType() . "_style"));
        $form->setFormAction($this->ctrl->getFormAction($this));
        
        return $form;
    }

    public function createStyle()
    {
        $this->ctrl->redirectByClass("ilobjstylesheetgui", "create");
    }
        
    public function editStyle()
    {
        $this->ctrl->redirectByClass("ilobjstylesheetgui", "edit");
    }

    public function deleteStyle()
    {
        $this->ctrl->redirectByClass("ilobjstylesheetgui", "delete");
    }

    public function saveStyleSettings()
    {
        $ilSetting = $this->settings;
    
        if ($ilSetting->get("fixed_content_style_id") <= 0 &&
            (ilObjStyleSheet::_lookupStandard($this->object->getStyleSheetId())
            || $this->object->getStyleSheetId() == 0)) {
            $this->object->setStyleSheetId(ilUtil::stripSlashes($_POST["style_id"]));
            $this->object->update();
            
            ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
        }
        $this->ctrl->redirect($this, "editStyleProperties");
    }
}
