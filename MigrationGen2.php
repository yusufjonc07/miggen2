<?php

namespace yusufjonc07\miggen2;

/**
 * Class Module
 *
 * @package yusufjonc07\miggen2
 */
class MigrationGen2 extends \yii\gii\Generator
{

     /**
     * @var string
     */
    public $tables = '';

    /**
     * @var string
     */
    public $mysql = TRUE;
    /**
     * @var bool
     */
    public $mssql = FALSE;
    /**
     * @var bool
     */
    public $pgsql = FALSE;
    /**
     * @var bool
     */
    public $sqlite = FALSE;

    /**
     * @var string
     */
    public $mysql_options = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
    /**
     * @var string
     */
    public $mssql_options = '';
    /**
     * @var string
     */
    public $pgsql_options = '';
    /**
     * @var string
     */
    public $sqlite_options = '';


    /**
     * @var array
     */
    public $databaseTables = [];

    /**
     * @var bool
     */
    public $addIfThenStatements = TRUE;

    /**
     * @var string
     */
    public $tableOptions = '';

    /**
     * @var bool
     */
    public $addTableInserts = FALSE;

    /**
     * @var string
     */
    public $ForeignKeyOnDelete = 'CASCADE';

    /**
     * @var string
     */
    public $ForeignKeyOnUpdate = 'NO ACTION';

    /**
     * @return array
     */

    public function getName()
    {
        return 'Migration Generator';
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'Generate migration files by exsisting tables` schema';
    }

    function rules()
    {
        return [
            [['tables', 'databaseTables', 'databaseType'], 'required'],
            ['tableOptions', 'default', 'value' => '']
        ];
    }

    public function generate(){

        $initialTabLevel = 0;
        $output = new OutputString(['tabLevel' => $initialTabLevel]);
        $output_drop = new OutputString();
        $tables_value = '';

        $array = [];
        $array['inserts'] = [];
        $array['fk'] = [];
        $array['indexes'] = [];

        $foreignKeyOnUpdate = $_POST['MigrationUtility']['ForeignKeyOnUpdate'];
        $foreignKeyOnDelete = $_POST['MigrationUtility']['ForeignKeyOnDelete'];
        $tables_value = $_POST['MigrationUtility']['tables'];
        $ifThen = 1; //$_POST['MigrationUtility']['addIfThenStatements'];
        $addTableInserts = $_POST['MigrationUtility']['addTableInserts'];
        $tableOptions = [];
        $tableOptions['mysql'] = [$_POST['MigrationUtility']['mysql'], $_POST['MigrationUtility']['mysql_options']];
        $tableOptions['mssql'] = [$_POST['MigrationUtility']['mssql'], $_POST['MigrationUtility']['mssql_options']];
        $tableOptions['pgsql'] = [$_POST['MigrationUtility']['pgsql'], $_POST['MigrationUtility']['pgsql_options']];
        $tableOptions['sqlite'] = [$_POST['MigrationUtility']['sqlite'], $_POST['MigrationUtility']['sqlite_options']];

        $tables = trim($tables_value);
        $tables = preg_replace('/\s+/', ',', $tables);
        $tables = explode(',', $tables);

        $output->addStr('$tables = Yii::$app->db->schema->getTableNames();');
        $output->addStr('$dbType = $this->db->driverName;');

        foreach ($tableOptions as $k => $item) {
            $output->addStr('$tableOptions_' . $k . ' = "' . (($item[0]) ? $item[1] : '') . '";');
        }

        foreach ($tables as $table) {
            if (empty($table)) {
                continue;
            }
            $columns = \Yii::$app->db->getTableSchema($table);
            $prefix = \Yii::$app->db->tablePrefix;
            $table_prepared = str_replace($prefix, '', $table);
            $output->tabLevel = $initialTabLevel;
            foreach ($tableOptions as $dbType => $item) {
                if (!$item[0]) {
                    continue;
                }

                $output->addStr('/* ' . strtoupper($dbType) . ' */');
                $output->addStr('if (!in_array(\'' . $table . '\', $tables))  { ');
                if ($ifThen) {
                    $output->addStr('if ($dbType == "' . $dbType . '") {');
                    $output->tabLevel++;
                }
                $output->addStr('$this->createTable(\'{{%' . $table_prepared . '}}\', [');
                $output->tabLevel++;
                // Ordinary columns
                $k = 0;
                foreach ($columns->columns as $column) {
                    $appUtility = new AppUtility($column, $dbType);
                    $output->addStr($appUtility->string . "',");
                    if ($column->isPrimaryKey) {
                        $output->addStr($k . " => 'PRIMARY KEY (`" . $column->name . "`)',");
                    }
                    $k++;

                }

                $output->tabLevel--;
                $output->addStr('], $tableOptions_' . strtolower($dbType) . ');');
                if (in_array($dbType, ['mysql', 'mssql', 'pgsql']) && !empty($columns->foreignKeys)) {
                    foreach ($columns->foreignKeys as $fk) {
                        $link_table = '';
                        foreach ($fk as $k => $v) {
                            if ($k == '0') {
                                $link_table = $v;
                            } else {
                                $link_to_column = $k;
                                $link_column = $v;
                                $str = '$this->addForeignKey(';
                                $str .= '\'fk_' . $link_table . '_' . explode('.', microtime('usec'))[1] . '_' . substr("000" . sizeof($array['fk']), 2) . "',";
                                $str .= '\'{{%' . $table . '}}\', ';
                                $str .= '\'' . $link_to_column . '\', ';
                                $str .= '\'{{%' . $link_table . '}}\', ';
                                $str .= '\'' . $link_column . '\', ';
                                $str .= '\'' . $foreignKeyOnDelete . '\', ';
                                $str .= '\'' . $foreignKeyOnUpdate . '\' ';
                                $str .= ');';
                                $array['fk'][] = $str;

                            }
                        }
                    }


                }

                $table_indexes = Yii::$app->db->createCommand('SHOW INDEX FROM `' . $table . '`')->queryAll();

                $table_indexes_new = [];
                foreach ($table_indexes as $item) {
                    if ($item['Key_name'] != 'PRIMARY' ) {
                        $table_indexes_new[$item['Key_name']]['cols'][] = $item['Column_name'];
                        $table_indexes_new[$item['Key_name']]['Column_name'][] = $item['Column_name'];
                        $table_indexes_new[$item['Key_name']]['Non_unique'] = $item['Non_unique'];
                        $table_indexes_new[$item['Key_name']]['Table'] = $item['Table'];
                    }

                }

                foreach ($table_indexes_new as $item) {
                    $unique = ($item['Non_unique']) ? '' : '_UNIQUE';
                    $array['indexes'][] = [
                        'name' => 'idx' . $unique . '_' . implode("_" ,array_values($item['Column_name'])) . '_' . explode('.', microtime('usec'))[1] . '_' . substr("000" . sizeof($array['indexes']), -2),
                        'unique' => (($item['Non_unique']) ? 0 : 1),
                        'column' => implode(",", array_values($item['cols'])),//$item['Column_name'],
                        'table' => $item['Table'],
                    ];
                }
                
                if ($ifThen) {
                    $output->tabLevel--;
                    $output->addStr('}');
                }
                $output->addStr('}');
                $output->addStr(' ');

            }

            if ($addTableInserts) {
                $data = Yii::$app->db->createCommand('SELECT * FROM `' . $table . '`')->queryAll();
                foreach ($data as $row) {
                    $out = '$this->insert(\'{{%' . $table . '}}\',[';
                    foreach ($columns->columns as $column) {
                        $out .= "'" . $column->name . "'=>'" . addslashes($row[ $column->name ]) . "',";
                    }
                    $out = rtrim($out, ',') . ']);';
//                        $output->addStr($out);
                    $array['inserts'][] = $out;
                }
            }

        }

        /* INDEXES */
        if (sizeof($array['indexes'])) {
            $output->addStr(' ');
            foreach ($array['indexes'] as $item) {
                $str = '$this->createIndex(\'' . $item['name'] . '\',\'' . $item['table'] . '\',\'' . $item['column'] . '\',' . $item['unique'] . ');';
                $output->addStr($str);
            }
        }

        /* FK */
        if (sizeof($array['fk'])) {
            $output->addStr(' ');
            $output->addStr('$this->execute(\'SET foreign_key_checks = 0\');');
            foreach ($array['fk'] as $item) {
                $output->addStr($item);
            }
            $output->addStr('$this->execute(\'SET foreign_key_checks = 1;\');');
        }

        /* INSERTS */
        if (sizeof($array['inserts'])) {
            $output->addStr(' ');
            $output->addStr('$this->execute(\'SET foreign_key_checks = 0\');');
            foreach ($array['inserts'] as $item) {
                $output->addStr($item);
            }
            $output->addStr('$this->execute(\'SET foreign_key_checks = 1;\');');
        }

        /* DROP TABLE */
        foreach ($tables as $table) {
            if (!empty($table)) {
//                    $output_drop->addStr('$this->dropTable(\'' . $table . '\');');
                $output_drop->addStr('$this->execute(\'SET foreign_key_checks = 0\');');
                $output_drop->addStr('$this->execute(\'DROP TABLE IF EXISTS `' . $table . '`\');');
                $output_drop->addStr('$this->execute(\'SET foreign_key_checks = 1;\');');
            }
        }
    
    }


}
