<?php
/**
 * @author     <Stephan@SparklingSoftware.com.au>
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */

 
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_jiradata extends DokuWiki_Syntax_Plugin {

    var $jiradata_helper = null;
    
    function syntax_plugin_jiradata(){        
        $this->jiradata_helper =& plugin_load('helper', 'jiradata');
        if (is_null($this->jiradata_helper)) {
            msg('The jiradata plugin needs the jiradata helper which cannot be loaded', -1);
            return false;
        }        
    }

    /**
     * return some info
     */
    function getInfo(){
        return array(
        'author' => 'Stephan Dekker',
        'email'  => 'Stephan@SparklingSoftware.com.au',
        'date'   => @file_get_contents(dirname(__FILE__) . '/VERSION'),
        'name'   => 'JiraData Plugin',
        'desc'   => 'Loads data from JIRA using the JIRA Rest API library and renders the information as a table',
        'url'    => 'http://dokuwiki.org/plugin:jiradata',
        );
    }
    
    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }
    
    /**
     * What about paragraphs?
     */
    function getPType(){
        return 'normal';
    }
    
    /**
     * Where to sort in?
     */
    function getSort(){
        return 990;     //was 990
    }
    
    
    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~JIRADATA~~',$mode,'plugin_jiradata');
    }
    
    /**
     * Handle the match
     */
    
    function handle($match, $state, $pos, &$handler){
        $match_array = array();
        return $match_array;
    }
    
    /**
     * Create output
     */
    function render($format, &$renderer, $data) {
        $jql = 'project = ALM ORDER BY key';
        $improvements = $this->jiradata_helper->getData($jql);
        
        $renderer->doc .= '<table>';
        $renderer->doc .= '<th>ID</th><th>Title</th><th>Description</th>';
        foreach ($improvements as $improvement)
        {
            $renderer->doc .= '<tr><td>'.$improvement["key"].'</td><td>'.$improvement["title"].'</td><td>'.$improvement["description"].'</td></tr>';
        }
    
        $renderer->doc .= '</table>';

        return true;
    }
    

    
}


//Setup VIM: ex: et ts=4 enc=utf-8 :
?>
