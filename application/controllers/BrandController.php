<?php

/*
 * Brand Controller is for follwing features:
 *  - manage brands
 */
class BrandController extends MyController
{
    function adminlistAction()
    {
        $this->_helper->layout->setLayout("layout_admin");
        $this->view->title = "All Brands";
        $brandTable = new Brands();
        $order = "create_date desc";
        $this->view->brands = $brandTable->fetchAll(null, $order, null, null);
        //TODO: paging
    }

    function adminaddAction()
    {
        $this->_helper->layout->setLayout("layout_admin");
        if ($this->_request->isPost()) { //post method
            $formData = $this->_request->getPost();
            //get logo image
            $imgfile = $_FILES['logo'];
            $imgdata = null;
            if (is_array($imgfile)) {
                $name = $imgfile['name'];
                $type = $imgfile['type'];
                $size = $imgfile['size'];
                if(!preg_match('/^image\//i', $type) ? true : false) {
                    $this->view->error = "请上传正确的图片";
                } else if($size > 2000000) {
                    $this->view->error = "图片不得超过2M";
                } else {
                    $tmpfile = $imgfile['tmp_name'];
                    $file = fopen($tmpfile, "rb");
                    $imgdata = base64_encode(fread($file,$size));
                    fclose($file);
                    //save brand
                    $brandTable = new Brands();
                    $newBrand = $brandTable->createRecord($formData['name'],
                            $formData['company'],$formData['description'],$imgdata,$type);
                    if ($newBrand > 0) {
                        $result = "Success";
                        $this->_helper->redirector('adminlist','brand');
                    }
                    $this->view->error = "上传成功";
                }
            }
        } else { //get method

        }
    }

    public function getimageAction()
    {
        $this->_helper->layout->disableLayout();
        $id = (int) $this->_request->getParam('id', 0);
        $brandTable = new Brands();
        $brand = $brandTable->find($id)->current();
        //Zend_Debug::dump($photo);
        header("Content-type:$brand->logo_type");
        $this->view->image = base64_decode($brand->logo);
    }
}
