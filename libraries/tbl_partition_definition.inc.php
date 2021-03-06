<?php

use PMA\libraries\Template;

if (!isset($partitionDetails)) {

    $partitionDetails = array();

    // Extract some partitioning and subpartitioning parameters from the request
    $partitionParams = array(
        'partition_by', 'partition_expr', 'partition_count',
        'subpartition_by', 'subpartition_expr', 'subpartition_count'
    );
    foreach ($partitionParams as $partitionParam) {
        $partitionDetails[$partitionParam] = isset($_REQUEST[$partitionParam])
            ? $_REQUEST[$partitionParam] : '';
    }

    // Only LIST and RANGE type parameters allow subpartitioning
    $partitionDetails['can_have_subpartitions'] = isset($_REQUEST['partition_count'])
        && $_REQUEST['partition_count'] > 1
        && isset($_REQUEST['partition_by'])
        && ($_REQUEST['partition_by'] == 'RANGE' || $_REQUEST['partition_by'] == 'LIST');

    // Values are specified only for LIST and RANGE type partitions
    $partitionDetails['value_enabled'] = isset($_REQUEST['partition_by'])
        && ($_REQUEST['partition_by'] == 'RANGE'
        || $_REQUEST['partition_by'] == 'LIST');

    if (PMA_isValid($_REQUEST['partition_count'], 'numeric')
        && $_REQUEST['partition_count'] > 1
    ) { // Has partitions
        $partitions = isset($_REQUEST['partitions']) ? $_REQUEST['partitions'] : array();

        // Remove details of the additional partitions
        // when number of partitions have been reduced
        array_splice($partitions, $_REQUEST['partition_count']);

        for ($i = 0; $i < $_REQUEST['partition_count']; $i++) {
            if (! isset($partitions[$i])) { // Newly added partition
                $partitions[$i] = array(
                    'value_type' => '',
                    'value' => '',
                    'engine' => '',
                    'comment' => '',
                    'data_directory' => '',
                    'index_directory' => '',
                    'max_rows' => '',
                    'min_rows' => '',
                    'tablespace' => '',
                    'node_group' => '',
                );
            }

            $partition =& $partitions[$i];
            $partition['name'] = 'p' . $i;
            $partition['prefix'] = 'partitions[' . $i . ']';

            if (! isset($partition['value_type'])) { // Changing from HASH/KEY to RANGE/LIST
                $partition['value_type'] = '';
                $partition['value'] = '';
            }
            if (! isset($partition['engine'])) { // When removing subpartitioning
                $partition['engine'] = '';
                $partition['comment'] = '';
                $partition['data_directory'] = '';
                $partition['index_directory'] = '';
                $partition['max_rows'] = '';
                $partition['min_rows'] = '';
                $partition['tablespace'] = '';
                $partition['node_group'] = '';
            }

            if (PMA_isValid($_REQUEST['subpartition_count'], 'numeric')
                && $_REQUEST['subpartition_count'] > 1
                && $partitionDetails['can_have_subpartitions'] == true
            ) { // Has subpartitions
                $partition['subpartition_count'] = $_REQUEST['subpartition_count'];

                if (! isset($partition['subpartitions'])) {
                    $partition['subpartitions'] = array();
                }
                $subpartitions =& $partition['subpartitions'];

                // Remove details of the additional subpartitions
                // when number of subpartitions have been reduced
                array_splice($subpartitions, $_REQUEST['subpartition_count']);

                for ($j = 0; $j < $_REQUEST['subpartition_count']; $j++) {
                    if (! isset($subpartitions[$j])) { // Newly added subpartition
                        $subpartitions[$j] = array(
                            'engine' => '',
                            'comment' => '',
                            'data_directory' => '',
                            'index_directory' => '',
                            'max_rows' => '',
                            'min_rows' => '',
                            'tablespace' => '',
                            'node_group' => '',
                        );
                    }

                    $subpartition =& $subpartitions[$j];
                    $subpartition['name'] = 'p' . $i . 's' . $j;
                    $subpartition['prefix'] = 'partitions[' . $i . ']'
                        . '[subpartitions][' . $j . ']';
                }
            } else { // No subpartitions
                unset($partition['subpartitions']);
                unset($partition['subpartition_count']);
            }
        }
        $partitionDetails['partitions'] = $partitions;
    }
}

echo Template::get('columns_definitions/partitions')
    ->render(array('partitionDetails' => $partitionDetails));
