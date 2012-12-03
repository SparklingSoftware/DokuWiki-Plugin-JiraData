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

require_once(DOKU_PLUGIN.'jiradata/lib/Autoloader.php');

/**
 * This is the base class for all syntax classes, providing some general stuff
 */
class helper_plugin_jiradata extends DokuWiki_Plugin {

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
    
    function getData($jql) {
        global $conf;
        
        Jira_Autoloader::register();

        //$jiraURL = $conf['plugin']['jiradata']['jira_url'];
        $jiraURL = 'http://jira.global.thenational.com';        
        
        $api = new Jira_Api(
            $jiraURL,
            new Jira_Api_Authentication_Basic("sdekker", "secret123")
        );

        $walker = new Jira_Issues_Walker($api);
        $walker->push($jql, "key,summary");
        $walker->valid();
        
        $table = array();
        $total = $walker->getTotal();
        for ($index = 1; $index <= $total; $index++) {

            // Get the values from the JIRA issue
            $key = $walker->current()->getKey(); 
            $summary = $walker->current()->getSummary(); 
//            $description = $walker->current()->getDescription(); 
            $description = 'todo';

            // Copy the values into a result row
            $row = array(
                "key" => $key, 
                "title" => $summary, 
                "description" => $description
            );
            array_push(&$table, $row);                    

            // Move to the next issue
            $walker->next();  
        }

        return $table;
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
