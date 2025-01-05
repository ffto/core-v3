<?php
$groups = get_file('@env/groups.csv', null, []);
?>
<ul>
<?php foreach ($groups as $group): ?>
    <?php 
    $url = "/group/{$group['slug']}";
    ?>
    <li>
        <a href="<?php echo $url; ?>"><?php echo $group['title']; ?></a>
    </li>
<?php endforeach; ?>
</ul>

<a href="/group/new">Add group</a>