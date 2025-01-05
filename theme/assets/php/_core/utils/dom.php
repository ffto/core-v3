<?php 
function selector_to_xpath ($selector, $is_single=false){
    $css = $selector;

    // Force an xpath selector
    if (is_array($selector) && isset($selector['xpath'])){
        $selector = $selector['xpath'];
    }else{
        // JS ref: https://github.com/css2xpath/css2xpath/blob/master/index.js

        // remove spaces around operators
        $selector  = preg_replace('/\s*>\s*/', '>', $selector);
        $selector  = preg_replace('/\s*~\s*/', '~', $selector);
        $selector  = preg_replace('/\s*\+\s*/', '+', $selector);
        $selector  = preg_replace('/\s*,\s*/', ',', $selector);
        $selectors = preg_split('/\s+(?![^\[]+\])/', $selector);

        foreach ($selectors as &$selector) {
            // ,
            $selector = preg_replace('/,/', '|descendant-or-self::', $selector);
            // input:checked, :disabled, etc.
            $selector = preg_replace('/(.+)?:(checked|disabled|required|autofocus)/', '\1[@\2="\2"]', $selector);
            // input:autocomplete, :autocomplete
            $selector = preg_replace('/(.+)?:(autocomplete)/', '\1[@\2="on"]', $selector);
            // input:button, input:submit, etc.
            $selector = preg_replace('/:(text|password|checkbox|radio|button|submit|reset|file|hidden|image|datetime|datetime-local|date|month|time|week|number|range|email|url|search|tel|color)/', 'input[@type="\1"]', $selector);
            // foo[id]
            $selector = preg_replace('/(\w+)\[([_\w-]+[_\w\d-]*)\]/', '\1[@\2]', $selector);
            // [id]
            $selector = preg_replace('/\[([_\w-]+[_\w\d-]*)\]/', '*[@\1]', $selector);

                // new ones
                // foo[id^=foo]
                $selector = preg_replace('/\[([_\w-]+[_\w\d-]*)\^=[\'"]?(.*?)[\'"]?\]/', '[starts-with(@$1,"$2")]', $selector);


            // foo[id=foo]
            $selector = preg_replace('/\[([_\w-]+[_\w\d-]*)=[\'"]?(.*?)[\'"]?\]/', '[@\1="\2"]', $selector);

                
            // [id=foo]
            $selector = preg_replace('/^\[/', '*[', $selector);
            // div#foo
            $selector = preg_replace('/([_\w-]+[_\w\d-]*)\#([_\w-]+[_\w\d-]*)/', '\1[@id="\2"]', $selector);
            // #foo
            $selector = preg_replace('/\#([_\w-]+[_\w\d-]*)/', '*[@id="\1"]', $selector);
            // div.foo
            $selector = preg_replace('/([_\w-]+[_\w\d-]*)\.([_\w-]+[_\w\d-]*)/', '\1[contains(concat(" ",@class," ")," \2 ")]', $selector);
            // .foo
            $selector = preg_replace('/\.([_\w-]+[_\w\d-]*)/', '*[contains(concat(" ",@class," ")," \1 ")]', $selector);
            // div:first-child
            $selector = preg_replace('/([_\w-]+[_\w\d-]*):first-child/', '*/\1[position()=1]', $selector);
            // div:last-child
            $selector = preg_replace('/([_\w-]+[_\w\d-]*):last-child/', '*/\1[position()=last()]', $selector);
            // :first-child
            $selector = str_replace(':first-child', '*/*[position()=1]', $selector);
            // :last-child
            $selector = str_replace(':last-child', '*/*[position()=last()]', $selector);
            // :nth-last-child
            $selector = preg_replace('/:nth-last-child\((\d+)\)/', '[position()=(last() - (\1 - 1))]', $selector);
            // div:nth-child
            $selector = preg_replace('/([_\w-]+[_\w\d-]*):nth-child\((\d+)\)/', '*/*[position()=\2 and self::\1]', $selector);
            // :nth-child
            $selector = preg_replace('/:nth-child\((\d+)\)/', '*/*[position()=\1]', $selector);
            // :contains(Foo)
            $selector = preg_replace('/([_\w-]+[_\w\d-]*):contains\((.*?)\)/', '\1[contains(string(.),"\2")]', $selector);


            // new selectors ---------------------------------------------------
            // $selector = preg_replace('/:closest\(([^\)]+)\)/', '*/*[ancestor::\1]', $selector);
            $selector = preg_replace('/:closest\(([^\)]+)\)/', '/ancestor::\1[position() = 1]', $selector);
            // ancestor

            // new selectors ---------------------------------------------------


            // >
            $selector = preg_replace('/>/', '/', $selector);
            // ~
            $selector = preg_replace('/~/', '/following-sibling::', $selector);
            // +
            $selector = preg_replace('/\+([_\w-]+[_\w\d-]*)/', '/following-sibling::\1[position()=1]', $selector);
            $selector = str_replace(']*', ']', $selector);
            $selector = str_replace(']/*', ']', $selector);
        }

        // ' '
        $selector = implode('/descendant::', $selectors);
        $selector = 'descendant-or-self::' . $selector;
        // :scope
        $selector = preg_replace('/(((\|)?descendant-or-self::):scope)/', '.\3', $selector);
        // $element
        $sub_selectors = explode(',', $selector);

        foreach ($sub_selectors as $key => $sub_selector) {
            $parts = explode('$', $sub_selector);
            $sub_selector = array_shift($parts);

            if (count($parts) && preg_match_all('/((?:[^\/]*\/?\/?)|$)/', $parts[0], $matches)) {
                $results = $matches[0];
                $results[] = str_repeat('/..', count($results) - 2);
                $sub_selector .= implode('', $results);
            }

            $sub_selectors[$key] = $sub_selector;
        }

        $selector = implode(',', $sub_selectors);
    }

    // only the first node
    if ($is_single === true){
        $selector = '(' . $selector . ')[1]';
    }

    // _js($css, $selector);

    return $selector;
}

// TODO rename "to_dom"
function get_dom ($content, $args=''){
    // Already a dom
    if (is_object($content) && isset($content->_dom)){
        return $content;
    }

    $args = to_args($args, array(
        'type'      => null,
        'node'      => null,
        'namespace' => false,
        'cache'     => false,
        'debug'     => false,
    ), 'node');

    if (!($type = $args['type']) && is_string($content)){
        if (strpos($content, '<?xml') !== false)  $type = 'xml';
        else if (string_is_url($content))    $type = 'url';
        else if (to_filepath($content))      $type = 'path';
        else                                      $type = 'html';
        // if (strpos($content, '<!DOCTYPE html>') !== false) $type = 'html';
    }

    $is_html = false;
    $path    = null;
    $base    = null;
    if ($type === 'path'){
        $path    = $base = $content;
        $content = get_file($content);

        if ($content && strpos($content, '<html') !== false){
            $base    = $path;
            $is_html = true;
        }
    }else if ($type === 'url'){
        $path    = $content;
        $http    = http($content, ['base'=>false, 'cache'=>$args['cache'], 'return'=>true]);
        $base    = $http['base'];
        $content = $http['body'];
        $is_html = !!$content;
    }else if ($type === 'html'){
        $is_html = !!$content;
    }
    
    if (is_string($content)){
        if ($is_html){
            $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
        }   
        if ($args['namespace'] === false){
            $content = preg_replace('/xmlns\="[^"]+"/', '', $content);
        }
    }

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->encoding = 'utf-8';
    
    if (!$content){
        return;
    }

    set_error_handler(function (){});
    if ($is_html)   @$dom->loadHTML($content);
    else            @$dom->loadXML($content);
    restore_error_handler();

    $xpath = new DOMXPath($dom);

    if ($args['debug']){
        _err($dom);
    }

    $ctx = $dom;
    if ($args['node']){
        // TODO use callback too
        $ctx = $dom->getElementsByTagName($args['node'])->item(0);
    }

    if ($ctx){
        $ctx->_xpath = $xpath;
        $ctx->_dom   = $dom;
        $ctx->_type  = $type;
        $ctx->_base  = $base;
        // $ctx->_html  = $content;
        // $ctx->_path  = $path;
    }

    return $ctx;
}

function dom_get_attr ($node, $attr=null, $fallback=null){
    if (!is_a($node, 'DOMElement')) return $fallback;

    $value = null;
    $dom   = isset($node->_dom) ? $node->_dom : $node->ownerDocument;

    if ($attr === true){
        $value = array('tag'=>$node->tagName);
        if ($node->attributes->length){
            foreach($node->attributes as $i => $v){
                $value[$i] = $v->value;
            }
        }
        $value['html'] = dom_get_attr($node, 'html');
    }else if ($attr === ':attrs'){
        $value = [];
        if ($node->attributes->length){
            foreach($node->attributes as $i => $v){
                $value[$i] = $v->value;
            }
        }
    }else if ($attr === ':tag' || $attr === 'tag'){
        $value = $node->tagName;
    }else if ($attr === ':html' || $attr === 'html' || $attr === 'innerHTML'){
        $html = [];
        foreach ($node->childNodes as $child){
            $html[] = $dom->saveHTML($child);
        }
        $value = implode('', $html);
    }else if ($attr === ':outer' || $attr === 'outer' || $attr === 'outerHTML'){
        $value = $dom->saveHTML($node);
    }else if ($attr === ':text' || $attr === 'text'){
        $value = $node->textContent;
    }else if ($attr === ':remove'){
        $node->parentNode->removeChild($node);
        $value = $node;
    }else if (is_callback($attr)){
        $value = _apply($attr, $node, $dom, $node->_xpath);
    }else if (is_string($attr)){
        $attr  = $node->attributes->getNamedItem($attr);
        $value = $attr ? $attr->value : '';
    }else{
        return $node;
    }

    return is($value) ? $value : $fallback;
}

function dom_get_node ($node, $selector, $args=null){
    $node = get_dom($node);

    if (is_bool($args)){
        $args = array('attr'=>true);
    }

    $args = _args($args, array(
        'attr' => null,
    ), 'attr');

    $args['single'] = true;
    $nodes = dom_get_nodes($node, $selector, $args);

    return reset($nodes);
}

function dom_get_nodes ($node, $selector, $args=null){
    $node = get_dom($node);

    if (is_bool($args)){
        $args = array('attr'=>true);
    }else if (is_callback($args)){
        $args = array('attr'=>$args);
    }

    $args = _args($args, array(
        'attr'     => null,
        'single'   => false,
        'fallback' => null,
    ), 'attr');
    
    $xpath    = isset($node->_xpath) ? $node->_xpath : new DOMXpath($node->_dom);
    $selector = selector_to_xpath($selector, $args['single']);
    $nodes    = $xpath->query($selector, $node);
    $nodes    = $args['single'] && count($nodes) ? array($nodes[0]) : $nodes;
    
    return array_each($nodes, function ($v) use ($node, $args){
        $v->_dom   = $node->_dom;
        $v->_xpath = $node->_xpath;
        return dom_get_attr($v, $args['attr'], $args['fallback']);
    });
} 

function dom_replace_node ($node, $replace){
    $node = isset($node->_dom) ? $node : get_dom($node);

    // convert 
    if (is_string($replace)){
        $body = get_dom($replace, 'body');
        // _warn($replace, dom_get_attr($body, 'html'));
        $replace = $body->childNodes;
    }else if (!is_array($replace)){
        $replace = [$replace];
    }

    foreach ($replace as $n){
        if (!is_a($n, 'DOMNode')) continue;
        $n = $node->_dom->importNode($n, true);
        $node->parentNode->insertBefore($n, $node);
    }

    // remove the current node
    $node->parentNode->removeChild($node);
}
