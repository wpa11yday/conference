# WP CMB2 conditional logic
A lightweight WordPress plugin for adding conditional logic fields to CMB2 plugin.

#### For adding conditional fields, add a new `attributes` parameter: 
1. `data-conditional-id` which should match the conditional feild ID.
2. `data-conditional-value` which should match the conditional value.

```php
    $cmb_demo->add_field( array(
        'name'          => __( 'Conditional select test:', 'your-text-domain' ),
        'desc'          => __('some description', 'your-text-domain'),
        'id'            => 'test_select_id',
        'type'          => 'select',
        'options'       => array(
            'one'    => __('one', 'your-text-domain' ),
            'two'    => __('two', 'your-text-domain'),
            'three'  => __('three', 'your-text-domain'),
        ),
    ) );
    // conditional field
    $cmb_demo->add_field(array(
        'name'          => __( 'Will show on value two selected', 'your-text-domain' ),
        'desc'          => __('some description', 'your-text-domain'),
        'id'            => 'test_select_depend',
        'type'          => 'select',
        'options'       => array(
            'four'   => __('Four', 'your-text-domain'),
            'five'   => __('Five', 'your-text-domain'),
            'six'    => __('Six', 'your-text-domain'),
        ),
        'attributes'    => array(
            'data-conditional-id'     => 'test_select_id',
            'data-conditional-value'  => 'two',
        ),
    ) );
```
#### You can add more than 1 value to the `data-conditional-value` using ```php wp_json_encode( array( 'value 1', 'value 2' ) ) ``` like so:

```php
        'attributes'    => array(
            'data-conditional-id'     => 'test_select_id',
            'data-conditional-value'  => wp_json_encode( array( 'two', 'three' ) ),
        ),
```


#### If you like to use this plugin as a stand-alone plugin check my [conditionalScript](https://awran5.github.io/conditional-script/)
