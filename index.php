<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
function buildCategoryTree(array $elements, $parentId = 0) {
    $branch = [];

//echo "<pre>";print_r($elements);print_r($parentId);
    foreach ($elements as $element) {
        //$element['level'] = $depth;
        if ($element['id_parent'] == $parentId) {
            $children = buildCategoryTree($elements, $element['id']);
            if ($children) {
                $element['children'] = $children;
            }
            $branch[] = $element;
        }
    }

    return $branch;
}

function buildTreeIds(array $elements, $parentId = 0)
{
    $ids = [];
//echo "<pre>";print_r($elements);print_r($parentId);
    foreach ($elements as $element) {
        //$element['level'] = $depth;
        if ($element['id_parent'] == $parentId ) {
            $ids = buildTreeIds($elements, $element['id']);
            $ids[] = $element['id'];
        }
    }

    return $ids;
}

function renderCategoryTree($categoryTree, $html = "") {
    $html .= '<ul>';
    foreach ($categoryTree as $category) {
        $html .= "<li><a href = '/?group=". $category['id'] . "'>". $category['name']."</a>";
        if (!empty($category['children'])) {
            $html .= renderCategoryTree($category['children']);
        }
        $html .= '</li>';
    }
    $html .= '</ul>';

    return $html;
}

function renderProducts($categoryTree) {
    $html = '<ul>';
    foreach ($categoryTree as $category) {
        $html .= '<li>' . $category['name'];
        $html .= '</li>';
    }
    $html .= '</ul>';

    return $html;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=catalog", "root", "root");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$categoryId = 0;
if (!empty($_GET['group'])) {
    $categoryId = $_GET['group'];
}

$sql = $pdo->prepare("SELECT * FROM `groups`");
$sql->execute();
$rows = $sql->fetchAll();
//echo "<pre>";print_r($rows);

$categoryTree = buildCategoryTree($rows);
//echo "<pre>";print_r($categoryTree);exit;

    $categoryTreeRender = renderCategoryTree($categoryTree);

    $ids = buildTreeIds($rows, $categoryId);
    $sql = $pdo->prepare("SELECT * FROM `products` WHERE `id_group` IN (".implode(',',$ids).")");
    $sql->execute();
    $rows = $sql->fetchAll();
    $products = renderProducts($rows);


// $categoryTree from step 2

//echo "<pre>";print_r(implode(',', array_column($categoryTree, 'id')));exit;


    //echo "<pre>";print_r($rows);exit;



//echo "<pre>";print_r($categoryTree);exit;
?>
<table>
    <tr>
        <th>Categories</th>
        <th>Products</th>
    </tr>
    <tr>
        <td><?= $categoryTreeRender?></td>
        <td><?= $products?></td>
    </tr>
</table>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<table>
    <tr>
        <th>Categories</th>
        <th>Products</th>
    </tr>
    <tr>
        <td>
            <a id='category-a' style='text-decoration:none; color:black;' href='#'>
                <span  id='category-symbol'>+ </span>
                Category
            </a>
            <ul class='child'  id='category'>
                <?= $categoryTreeRender ?>
            </ul>
        </td>
        <td>
            <a id='category2-a' style='text-decoration:none; color:black;' href='#'>
                <span  id='category2-symbol'>+ </span>
                Products
            </a>
            <ul class='child'  id='category2'>
                <?= $products ?>
            </ul>
        </td>

    </tr>
</table>

<script>
    $(document).ready(function(){
        $('.child').hide();
    });
    $( "a" ).click(function() {
        Show(this);
    });

    function Show(obj)
    {
        var ObjId = obj.id;
        var Obj = ObjId.split('-');
        var id = Obj[0];

        var symb = $('#'+id+'-symbol').html();

        if(symb.trim() == "+")
        {
            $('#'+id).show(1000);
            $('#'+id+'-symbol').html("- ");
        }
        else
        {
            $('#'+id).hide(1000);
            $('#'+id+'-symbol').html("+ ");
        }

    }
/*
    $.ajax({
        url: '/index.php',
        method: 'get',
        dataType: 'html',
        data: {group: '2'},
        success: function(data){
            alert(data);
        }
    });
*/
</script>
