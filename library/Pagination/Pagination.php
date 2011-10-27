<?php
class XY_Pagination
{
    private $_navigationItemCount = 10;                //the total number of pages showed in Navigation bar 
    private $_pageSize = null;                        //the total number of records pre page
    private $_align = "right";                        //the location of Navigation bar
    private $_itemCount = null;                        //the total number of records
    private $_pageCount = null;                        //the total number of pages
    private $_currentPage = null;                    //the current page number
    private $_front = null;                            //Front-end controller 
    private $_PageParaName = "page";                //Page parameter name 

    private $_firstPageString = "|<<";                //shows the first page 
    private $_nextPageString = ">>";                //shows the next page
    private $_previousPageString = "<<";            //shows the previous page
    private $_lastPageString = ">>|";                //shows the last page
    private $_splitString = "  ";					 //the character between the page number

    public function __construct($itemCount, $pageSize)
    {
        if(!is_numeric($itemCount) || (!is_numeric($pageSize)))
        throw new Exception("Pagination Error:not Number");
        $this->_itemCount = $itemCount;
        $this->_pageSize = $pageSize;
        $this->_front = Zend_Controller_Front::getInstance();

        $this->_pageCount = ceil($itemCount/$pageSize);            //the total page
        $page = $this->_front->getRequest()->getParam($this->_PageParaName);
        if(empty($page) || (!is_numeric($page)))    
        {
            $this->_currentPage = 1;
        }
        else
        {
            if($page < 1)
                $page = 1;
            if($page > $this->_pageCount)
                $page = $this->_pageCount;
            $this->_currentPage = $page;
        }
    }

    /**
     * return current page number
     * @param int _currentPage
     */
    public function getCurrentPage()
    {
        return $this->_currentPage;
    }

    /**
     * return navigation bar
     * @return string html                class="PageNavigation" 
     */
    public function getNavigation()
    {
        $navigation = '<div style="text-align:'.$this->_align.'">';

        $pageCote = ceil($this->_currentPage / ($this->_navigationItemCount - 1)) - 1;    //the location cote of current page in the navigation当前页处于第几栏分页
        $pageCoteCount = ceil($this->_pageCount / ($this->_navigationItemCount - 1));    //the total page cote
        $pageStart = $pageCote * ($this->_navigationItemCount -1) + 1;                    //the start page in cote
        $pageEnd = $pageStart + $this->_navigationItemCount - 1;                        //the end page in cote
        if($this->_pageCount < $pageEnd)
        {
            $pageEnd = $this->_pageCount; //the total number of page
        }
//                $navigation .= "(In all)总共：{$this->_pageCount}(Page)页\n";

		//config Zend_Translate for Pagination.php
		$config = new Zend_Config_Ini("../application/config.ini","dev");
		$langNamespace = new Zend_Session_Namespace('Lang');
		$translate = new Zend_Translate('tmx', strval($config->framework->language->dir), $langNamespace->lang);
		
		$navigation .= $translate->translate("His_Page").":";
		
        if($pageCote > 0)                                //the first page navigation
        {
            $navigation .= '<a href="'.$this->createHref(1)."\">$this->_firstPageString</a> ";
        }
        if($this->_currentPage != 1)                    //the previous page navigation
        {
            $navigation .= '<a href="'.$this->createHref($this->_currentPage-1);
            $navigation .= "\">$this->_previousPageString</a> ";
        }
    	if($this->_currentPage == 1)                    //the previous page navigation
        {
            $navigation .= '<a href="'.$this->createHref($this->_currentPage);
            $navigation .= "\">$this->_previousPageString</a> ";
        }
        while ($pageStart <= $pageEnd)                    //config pages' navigation
        {
            if($pageStart == $this->_currentPage)
            {
                $navigation .= "<strong>$pageStart</strong>".$this->_splitString;
            }
            else
            {
                $navigation .= '<a href="'.$this->createHref($pageStart)."\">$pageStart</a>".$this->_splitString;
            }
            $pageStart++;
        }
        if($this->_currentPage != $this->_pageCount)    //the next page navigation
        {
            $navigation .= ' <a href="'.$this->createHref($this->_currentPage+1)."\">$this->_nextPageString</a> ";
        }
    	if($this->_currentPage == $this->_pageCount)    //the next page navigation
        {
            $navigation .= ' <a href="'.$this->createHref($this->_currentPage)."\">$this->_nextPageString</a> ";
        }
        if($pageCote < $pageCoteCount-1)                //the last page navigation
        {
            $navigation .= '<a href="'.$this->createHref($this->_pageCount)."\">$this->_lastPageString</a> ";
        }
        //add 'direct navigation select'
        //$navigation .= '<input type="text" size="3" onkeydown="if(event.keyCode==13){window.location=\' ';
        //$navigation .= $this->createHref().'\'+this.value;return false;}" />';
        
        //Bug fix(2008.8.27): there is an error when we input the wrong page number  ------begin
//        $navigation .= '  <select onchange="window.location=\' '.$this->createHref().'\'+this.options[this.selectedIndex].value;">';
//        for ($i=1;$i<=$this->_pageCount;$i++){
//                if ($this->getCurrentPage()==$i){
//                        $selected = "selected";
//                }
//                else {
//                        $selected = "";
//                }
//                $navigation .= '<option value='.$i.' '.$selected.'>'.$i.'</option>';
//        }
//        $navigation .= '</select>';
        //Bug fix(2008.8.27): there is an error when we input the wrong page number  ------end
        
        $navigation .= "</div>";
        return $navigation;
    }

    /**
     * @return int 
     */
    public function getNavigationItemCount()
    {
        return $this->_navigationItemCount;
    }

    /**
     * @param  int $navigationCount
     */
    public function setNavigationItemCoun($navigationCount)
    {
        if(is_numeric($navigationCount))
        {
            $this->_navigationItemCount = $navigationCount;
        }
    }

    /**
     * @param string $firstPageString 
     */
    public function setFirstPageString($firstPageString)
    {
        $this->_firstPageString = $firstPageString;
    }

    /**
     * @param string $previousPageString
     */
    public function setPreviousPageString($previousPageString)
    {
        $this->_previousPageString = $previousPageString;
    }

    /**
     * @param string $nextPageString
     */
    public function setNextPageString($nextPageString)
    {
        $this->_nextPageString = $nextPageString;
    }

    /**
     * @param string $nextPageString
     */
    public function setLastPageString($lastPageString)
    {
        $this->_lastPageString = $lastPageString;
    }

    /**
     * @param string $align
     */
    public function setAlign($align)
    {
        $align = strtolower($align);
        if($align == "center")
        {
            $this->_align = "center";
        }elseif($align == "right")
        {
            $this->_align = "right";
        }else
        {
            $this->_align = "left";
        }
    }
    /**
     * @param string $pageParamName
     */
    public function setPageParamName($pageParamName)
    {
        $this->_PageParaName = $pageParamName;
    }

    /**
     * @return string 
     */
    public function getPageParamName()
    {
        return $this->_PageParaName;
    }

    /**
     * create the link of navigation
     * @param int $targetPage:the navigation page
     * @return string $targetUrl
     */
    private function createHref($targetPage = null)
    {
        $params = $this->_front->getRequest()->getParams();
                $module = $params["module"];
        $controller = $params["controller"];
        $action = $params["action"];

        $targetUrl = $this->_front->getBaseUrl()."/$module/$controller/$action";
        foreach ($params as $key => $value)
        {
            if($key != "controller" && $key != "module" && $key != "action" && $key != $this->_PageParaName)
            {
                $targetUrl .= "/$key/$value";
            }
        }
        if(isset($targetPage))                
            $targetUrl .= "/$this->_PageParaName/$targetPage";
        else
            $targetUrl .= "/$this->_PageParaName/";
        return $targetUrl;
    }
}

?>