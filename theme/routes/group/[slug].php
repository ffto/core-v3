<?php 
// [ ] keep in cache the last "unit"

$slug   = _request('slug');
$groups = get_file('@env/groups.csv', null, []);
$group  = array_find($groups, ['slug'=>$slug], true);
$people = array_each($group['people'], '/(?<name>.+) <(?<email>.*)>/');

if (is_post()){
    $amount     = _request('amount');
    $unit       = _request('unit');
    $conversion = to_currency($amount, $unit, 'CAD');
    $split_with = _request('split_with');
    $split_with = implode(', ', $split_with);
    
    $row = [
        'timestamp'   => to_date('now', ':utc'),
        'group'       => $slug,
        'description' => _request('description'),
        'amount'      => $amount,
        'unit'        => $unit,
        'conversion'  => $conversion,
        'paid_by'     => _request('paid_by'),
        'split_with'  => $split_with,
    ];
    set_file('@env/expenses.csv', $row, true);

    _p($row);    
}
?>

<form method="post">
    <?php 
    echo to_text_field('description', 'title=Description&placeholder=Bob');
    echo to_text_field('amount', 'title=Amount');

    echo '<div class="dropdown">';
    echo to_select_field('unit', 'title=Unit', [
        'CAD' => 'CAD',
        'USD' => 'USD',
        'MXN' => 'MX (Pesos)',
    ]);
    echo '</div>';

    echo to_select_field('paid_by', 'title=Paid by&value_key=name&label_key=name', $people);
    echo to_checkboxes_field('split_with', 'title=Split between&value_key=name&label_key=name', $people);

    echo to_radios_field('test', 'title=TEST&value_key=name&label_key=name', $people);

    echo to_footer_field();
    ?>
</form>
