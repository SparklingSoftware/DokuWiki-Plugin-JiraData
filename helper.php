<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_INC.'inc/infoutils.php');

require_once(DOKU_PLUGIN.'jira/lib/Autoloader.php');

/**
 * This is the base class for all syntax classes, providing some general stuff
 */
class helper_plugin_jira extends DokuWiki_Plugin {

    function getMethods(){
        $result = array();
        $result[] = array(
          'name'   => 'getJiraData',
          'desc'   => 'returns jira data based on the jql statement',
          'params' => array(
            'Jira Query (jql)' => 'string'),
          'return' => array('Query result' => 'array'),
        );
        // and more supported methods...
        return $result;
    }
    
    function getMockData() {
        $table = array();
        
        $row = array(
            "key" => "IP-1", 
            "title" => "Improve the IT Industry", 
            "description" => "As an IT industry we owe it to ourselfs to deliver more value to our customers"
            );
        array_push(&$table, $row);        
        $row = array(
            "key" => "IP-165", 
            "title" => "Improvement 165", 
            "description" => "Improvement 165 description"
            );
        array_push(&$table, $row);        
        
        for ($i=501; $i<=550; $i++)
        {
            $row = array(
                "key" => "IP-".$i, 
                "title" => "Improvement ".$i, 
                "description" => "Improvement ".$i." description"
                );

            array_push(&$table, $row);
        }
        
        return $table;
    }
    
    function getJiraData($jql){        
        $table = $this->getMockData();
        return $table;
    }
    
    function doWork() {
        Jira_Autoloader::register();

        $api = new Jira_Api(
            "http://jira:8080",
            new Jira_Api_Authentication_Basic("sdekker", "SparklingSoftware123")
        );

        $jql = 'project = SEPG AND (status = "In Progress" or status = "Awaiting Signoff") ORDER BY key';
        $walker = new Jira_Issues_Walker($api);
        $walker->push($jql, "key,summary");
        $walker->valid();
    }
        
    /**
     * load the sqlite helper for caching
     */
    function _getDB(){
        $db =& plugin_load('helper', 'sqlite');
        if (is_null($db)) {
            msg('The data plugin needs the sqlite plugin', -1);
            return false;
        }
        if($db->init('data',dirname(__FILE__).'/db/')){
            sqlite_create_function($db->db,'DATARESOLVE',array($this,'_resolveData'),2);
            return $db;
        }else{
            return false;
        }
    }



}
