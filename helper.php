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

    var $sqlite = null;

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
        
    // The summary is the issue title
    function getSummary($key)
    {
        $res = $this->loadSqlite();
        if (!$res) return;

        $cacheTimedOut = hasLocalCacheTimedOut($key);
        if ($cacheTimedOut)
        { 
             // Get fresh data from JIRA
             $issue = $this->getIssue($key);
             $summary = $issue["title"];
        }
        else {
            // Read the info from the cache
            $res = $this->sqlite->query("SELECT summary FROM jiradata WHERE key = '".$key."'");
            $summary = sqlite_fetch_single($res);
        }

        if (!$summary) return $key;
        return $summary;        
    }
    
    function getIssue($key) {
        global $conf;
        $this->getConf('');
        $project = $conf['plugin']['jiradata']['jira_project_id'];        
        
        $jql = "project = ".$project." and key = ".$key;
        $issues =  $this->getIssues($jql);
        
        // There should only be one
        return $issues[0];        
    }
    
    function getIssues($jql) {
        global $conf;
        $this->getConf('');
        
        $project = $conf['plugin']['jiradata']['jira_project_id'];        
        $integrationEnabled = $conf['plugin']['jiradata']['jira_integration_enabled'];
        if ($integrationEnabled === 0) {
            $table = array();
            $row = array( "key" => $project.'-9999',  "title" => 'JIRA Integration disabled', "description" => 'JIRA Integration disabled');
            array_push($table, $row);                                
            $row = array( "key" => $project.'-9998',  "title" => 'JIRA Integration disabled', "description" => 'JIRA Integration disabled');
            array_push($table, $row);                                
            $row = array( "key" => $project.'-9997',  "title" => 'JIRA Integration disabled', "description" => 'JIRA Integration disabled');
            array_push($table, $row);                                
            return $table;
        }
                
        $jiraURL = $conf['plugin']['jiradata']['jira_url'];    
        $username = $conf['plugin']['jiradata']['jira_username'];    
        $password = $conf['plugin']['jiradata']['jira_password'];    

        $headers = @get_headers($jiraURL."/rest/api/latest/serverInfo");
        if(strpos($headers[0],'200')===false) {        
            throw new Exception("Error connecting to JIRA: ".$jiraURL);
        }
        
        // Debug info only:
        // $msg = 'Username: '.$username.' Password: '.$password;
        // msg($msg);

        Jira_Autoloader::register();
        $api = new Jira_Api(
            $jiraURL,
            new Jira_Api_Authentication_Basic($username, $password)
        );

        $walker = new Jira_Issues_Walker($api);
        $walker->push($jql, "key, summary, description");
        $walker->valid();        
        
        $table = array();
        foreach ($walker as $issue) {
            $key = $walker->current()->getKey();             
            $summary = $walker->current()->getSummary(); 
            $description = $walker->current()->getDescription(); 
            
            $row = array(
                "key" => $key, 
                "title" => $summary, 
                "description" => $description
            );
            array_push($table, $row);                    

            $this->updateCache($key, $summary, $description);
        }        

        return $table;
    }


    function loadSqlite()
    {
        // Columns:
        // key
        // summary
        // description
        // timestamp
    
        if ($this->sqlite) return true;

        $this->sqlite =& plugin_load('helper', 'sqlite');
        if (is_null($this->sqlite)) {
            msg('The sqlite plugin could not loaded from the jiradata Plugin helper', -1);
            return false;
        }
        if($this->sqlite->init('jiradata',DOKU_PLUGIN.'jiradata/db/')){
            return true;
        }else{
             msg('The jiradata cache failed to initialise.', -1);
            return false;
        }                 
    }
    
    function hasLocalCacheTimedOut($key)
    {
        $hasCacheTimedOut = true;

        $res = $this->loadSqlite();
        if (!$res) return;
        
        $res = $this->sqlite->query("SELECT timestamp FROM jiradata WHERE key = '".$key."';");
        $timestamp = (int) sqlite_fetch_single($res);
        if ($timestamp < time() - (60 * 30))  // 60 seconds x 5 minutes
        { 
            $hasCacheTimedOut = true; 
        }
        
        return $hasCacheTimedOut;
    }
    
    
    function updateCache($key, $summary, $description) {
        $res = $this->loadSqlite();
        if (!$res) 
        {
            msg('Error loading sqlite');
            return;
        }

        // Set the time to zero, so the first alert msg will set the correct status
        $sql = "INSERT OR REPLACE INTO jiradata (id, summary, description, timestamp) VALUES ('".$key."', '".$summary."', '".$description."', ".time().");";
        $this->sqlite->query($sql);
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
