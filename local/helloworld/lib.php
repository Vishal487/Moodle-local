<?php
/**
 * Add link to index.php into navigation drawer.
 *      
 * @param global_navigation $nav Node representing the global navigation tree.
 */
function local_helloworld_extend_navigation_frontpage(navigation_node $nav) {
    $node = $nav->add(get_string('pluginname','local_helloworld'),'/local/helloworld/', navigation_node::NODETYPE_LEAF,null,null, new pix_icon('i/report', 'grades'));
    $node->nodetype=1;
    $node->collapse=false;
    $node->forceopen=true;
    $node->isexpandable=false;
    $node->showinflatnavigation=true;

}
