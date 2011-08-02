<?php

include 'rest-utils.php';
include 'umd-api.php';
include 'str-utils.php';

$request = RestUtils::processRequest();

switch ($request->getMethod()) {
    case 'get':
        $umd_api = new umd_api;
        $data = null;
        $request_vars = $request->getRequestVars();
        $model = isset($request_vars['model']) ? $request_vars['model'] : null;
        $format = isset($request_vars['format']) ? $request_vars['format'] : 'object';
        
        switch ($model) {
            case 'course':
                $data = array();
                if ($request->getData()) {
                    foreach ($request->getData() as $i => $data_i) {
                        $year = isset($data_i->year) ? $data_i->year : null;
                        $term = isset($data_i->term) ? $data_i->term : null;
                        $dept = isset($data_i->dept) ? $data_i->dept : null;
                        $sec  = isset($data_i->sec)  ? $data_i->sec  : null;
                        
                        $new_data = $umd_api->get_schedule($year, $term, $dept, $sec);
                        if ($format == 'events')
                            $new_data = sectionToEvents($new_data->courses[0]->sections[0], $new_data->courses[0], $i / count($request->getData()));
                        
                        $data = array_merge($data, $new_data);
                    }
                }
                break;
            case 'dept':
                $year = ($request->getData() && isset($request->getData()->year)) ? $request->getData()->year : null;
                $term = ($request->getData() && isset($request->getData()->term)) ? $request->getData()->term : null;
                $data = $umd_api->get_schedule($year, $term);
                break;
            default:
                RestUtils::sendResponse(501, null, 'text/html');
        }
        
        if ($data === null)
            RestUtils::sendResponse(404, null, 'text/html');

        if (true || $request->getHttpAccept() == 'json') {
            RestUtils::sendResponse(200, json_encode($data), 'application/json');
        }
        else if ($request->getHttpAccept() == 'xml') {
            // using the XML_SERIALIZER Pear Package
            $options = array
            (
                'indent' => '    ',
                'addDecl' => false,
                'rootName' => 'root',
                XML_SERIALIZER_OPTION_RETURN_RESULT => true
            );
            $serializer = new XML_Serializer($options);

            RestUtils::sendResponse(200, $serializer->serialize($data), 'application/xml');
        }

        break;
        /*
    // new user create
    case 'post':
        $user = new User();
        $user->setFirstName($request->getData()->first_name);  // just for example, this should be done cleaner
        // and so on...
        $user->save();

        // just send the new ID as the body
        RestUtils::sendResponse(201, $user->getId());
        break;
        */
}


?>