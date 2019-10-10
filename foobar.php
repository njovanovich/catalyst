<?php
/**
 * foobar.php
 * Created by: nick
 * @ 10/10/2019 9:28 PM
 * Project: catalyst
 *
 */

for ($i=1; $i<=100; $i++)
{
    switch ($i) {
        case (($i % 3 == 0) && ($i % 5 == 0)):
            echo "foobar, ";
            break;

        case ($i % 5 == 0):
            echo "bar, ";
            break;

        case ($i % 3 == 0):
            echo "foo, ";
            break;

        default:
            echo "$i, ";

    }
}
echo "\n"; 
