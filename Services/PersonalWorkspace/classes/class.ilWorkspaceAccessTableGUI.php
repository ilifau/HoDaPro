<?php
/* Copyright (c) 2010 Leifos, GPL, see docs/LICENSE */

include_once("./Services/Table/classes/class.ilTable2GUI.php");

/**
 * Workspace access handler table GUI class
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.de>
 * @version $Id: class.adnCountryTableGUI.php 27876 2011-02-25 16:51:38Z jluetzen $
 *
 * @ingroup ServicesPersonalWorkspace
 */
class ilWorkspaceAccessTableGUI extends ilTable2GUI
{
    /**
     * @var ilCtrl
     */
    protected $ctrl;

    protected $node_id; // [int]
    protected $handler; // [ilWorkspaceAccessHandler]

    /**
     * Constructor
     *
     * @param object $a_parent_obj parent gui object
     * @param string $a_parent_cmd parent default command
     * @param int $a_node_id current workspace object
     * @param object $a_handler workspace access handler
     */
    public function __construct($a_parent_obj, $a_parent_cmd, $a_node_id, $a_handler, $a_page_id = null)
    {
        global $DIC;

        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $ilCtrl = $DIC->ctrl();
        $lng = $DIC->language();

        $this->node_id = $a_node_id;
        $this->handler = $a_handler;
        if($a_page_id){
        	$this->page_id = $a_page_id;
		}

        parent::__construct($a_parent_obj, $a_parent_cmd);

        $this->setId("il_tbl_wsacl");

        $this->setTitle("Seite: ".ilPortfolioPage::lookupTitle($a_page_id). " freigabe");
                
        $this->addColumn($this->lng->txt("wsp_shared_with"), "title");
        $this->addColumn($this->lng->txt("details"), "type");
        $this->addColumn($this->lng->txt("actions"));
		$this->addColumn("Feedback");
        
        $this->setDefaultOrderField("title");
        $this->setDefaultOrderDirection("asc");

        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
        $this->setRowTemplate("tpl.access_row.html", "Services/PersonalWorkspace");

        $this->importData();
    }

    /**
     * Import data from DB
     */
    public function importData()
    {
        include_once("./Services/User/classes/class.ilUserUtil.php");
        
        $data = array();
        foreach ($this->handler->getPermissions($this->node_id) as $obj_id) {
            // title is needed for proper sorting
            // special modes should always be on top!
            $title = null;

            //Fau: Show specific pages
            if(ilPortfolioAccessHandler::getExtendedData($this->node_id, $obj_id) != null){
            	$pages = explode("_",ilPortfolioAccessHandler::getExtendedData($this->node_id, $obj_id));
				$entry_data = $this->importData2($obj_id);
            	foreach ($pages as $page){
					if ($entry_data[0]) {
						$data[] = array("id" => $obj_id,
								"title" => $entry_data[0],
								"caption" => $entry_data[1],
								"type" => "Seite " . ilPortfolioPage::lookupTitle($page),
								"request" => $page);
					}
				}
			}else{
            	$entry_data = $this->importData2($obj_id);
				if ($entry_data[0]) {
					$data[] = array("id" => $obj_id,
						"title" => $entry_data[0],
						"caption" => $entry_data[1],
						"type" => $entry_data[2],
						"request" => "requestFeedback");
				}
			}
            

        }
        $this->setData($data);
    }
    
    /**
     * Fill table row
     *
     * @param array $a_set data array
     */
    protected function fillRow($a_set)
    {
        $ilCtrl = $this->ctrl;

        // properties
        $this->tpl->setVariable("TITLE", $a_set["caption"]);
        $this->tpl->setVariable("TYPE", $a_set["type"]);

        $ilCtrl->setParameter($this->parent_obj, "obj_id", $a_set["id"]);
        $this->tpl->setVariable(
            "HREF_CMD",
            $ilCtrl->getLinkTarget($this->parent_obj, "removePermission")
        );
        $link = $ilCtrl->getLinkTarget($this->parent_obj, "requestFeedback");
        //if($a_set["request"] != $_REQUEST["user_page"]){
		//	$link = str_replace("user_page=".$_REQUEST["user_page"],"user_page=".$a_set["request"],$raw_link);
		//}
        //if($a_set["caption"] != "Alle registrierten Benutzer"){
			$this->tpl->setVariable(
				"HREF_CMD2",
				$link
			);
		//}
        $this->tpl->setVariable("TXT_CMD", $this->lng->txt("remove"));
    }

    public function importData2($obj_id){
		switch ($obj_id) {
			case ilWorkspaceAccessGUI::PERMISSION_REGISTERED:
				$caption = $this->lng->txt("wsp_set_permission_registered");
				$title = "0" . $caption;
				break;

			case ilWorkspaceAccessGUI::PERMISSION_ALL_PASSWORD:
				$caption = $this->lng->txt("wsp_set_permission_all_password");
				$title = "0" . $caption;
				break;

			case ilWorkspaceAccessGUI::PERMISSION_ALL:
				$caption = $this->lng->txt("wsp_set_permission_all");
				$title = "0" . $caption;
				break;

			default:
				$type = ilObject::_lookupType($obj_id);
				$type_txt = $this->lng->txt("obj_" . $type);

				if ($type === null) {
					// invalid object/user
				} elseif ($type != "usr") {
					$title = $caption = ilObject::_lookupTitle($obj_id);
				} else {
					$caption = ilUserUtil::getNamePresentation($obj_id, false, true);
					$title = strip_tags($caption);
				}
				break;
		}
		return array($title, $caption, $type_txt);
	}
}
