<?php
function debug_crosshair (){
    add_script_helper('debug_crosshair__js');
}
function debug_crosshair__js (){
    ?><script>Web.Element(function Crosshair (_){
        this.$is    = false;
        this.$style = {
            '\&'         : 'position:fixed; top:0; right:0; bottom:0; left:0; opacity:0.5; pointer-events:none; z-index:99999;',
            '\&:before'  : 'content:""; position:absolute; top:0; left:var(--x); height:100%; width:1px; background:black;',
            '\&:after'   : 'content:""; position:absolute; top:var(--y); left:0; width:100%; height:1px; background:black;',
        };

        this.isFrozen = false;

        // init ----------------------------------------------------------------
        this.init = function (){
            this.el = this.get('<div class="\&">', {'parent':'body'});
        };

        // events --------------------------------------------------------------
        this.onCursor = function (e){
            if (e.type === 'cursor-click'){
                this.isFrozen = !this.isFrozen;
            }

            !this.isFrozen && _.dom.style(this.el, {
                '--x' : e.x + 'px',
                '--y' : e.y + 'px',
            });
        };
    });</script><?php
}