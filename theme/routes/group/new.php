<?php 
if (is_post()){
    $title  = _request('title');
    $slug   = to_slug($title);
    $people = _request('people');
    // $people = to_string($people, 'pretty=0');
    $people = array_each($people, '{{name}} <{{email}}>', 'join=, ');

    $url = "/group/{$slug}";
    $row = [
        'title'  => $title,
        'slug'   => $slug,      // make sure the slug can't be duplicated
        'people' => $people,
    ];

    set_file('@env/groups.csv', $row, true);
    redirect($url);
}
?>
<form method="post">
    <?php 
    echo to_text_field('title', 'title=Group');
    echo to_repeater_field('people', [
        'name'  => ['title'=>'Name', 'type'=>'text'],
        'email' => ['title'=>'Email', 'type'=>'text'],
    ]); 
    echo to_footer_field();
    ?>
</form>