<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
function buildCategoryTree(array &$elements, $counts, $parentId = 0, $count=0)
{
    $branch = [];
//$count=0;
    foreach ($elements as &$element) {
        if ($element['id_parent'] == $parentId) {
            $children = buildCategoryTree($elements, $counts, $element['id'], $count);
            if ($children) {
                $element['children'] = $children;
                $element['count'] += count($children);
               // $count = 0;

                foreach ($children as &$child) {
                    $count+=$child['count'];
                }

            }
            $element['count'] += $count;
            $branch[] = $element;
        }
    }
    $count += count($elements);
    return $branch;
}

function buildCounts(&$tree, $counts)
{
    $count = 0;
    foreach ($tree as &$child) {


        if (isset($child['children']) && is_array($child['children'])) {
            $count+=count($child['children']);
            $count += buildCounts($child['children'], $counts);
        }
        $child['count'] = $count;
    }

    return $count;
}

function buildTreeIds(array $elements, $parentId = 0)
{
    $ids = [$parentId];

    foreach ($elements as $element) {
        if ($element['id_parent'] == $parentId) {
            $children = buildTreeIds($elements, $element['id']);
            $ids = array_merge($ids, $children);
            $ids[] = $element['id'];
        }
    }

    return $ids;
}

function renderCategoryTree($categoryTree, $parentId, $html = "")
{
    $html .= '<ul style="display:' . ($parentId ? "none" : "block") . '">';
    foreach ($categoryTree as $category) {
        $html .= "<li>
<a  id=" . $category['id'] . " href = '/?group=" . $category['id'] . "'>" . $category['name'].' ('.$category['count'].')' . "</a>";
        if (!empty($category['children'])) {
            $html .= renderCategoryTree($category['children'], $category['id']);
        }
        $html .= '</li>';
    }
    $html .= '</ul>';

    return $html;
}

function renderProducts($categoryTree)
{
    $html = "";
    foreach ($categoryTree as $category) {
        $html .= '<li>' . $category['name'];
        $html .= '</li>';
    }

    return $html;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=catalog", "root", "root");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$categoryId = 0;
if (!empty($_GET['group'])) {
    $categoryId = $_GET['group'];
}

$sql = $pdo->prepare("SELECT * FROM `groups`");
$sql->execute();
$categories = $sql->fetchAll();

$sql = $pdo->prepare("SELECT * FROM `products` ");
$sql->execute();
$products = $sql->fetchAll();
$counts = [];
foreach ($products as $item) {
    if (!isset($counts[$item['id_group']])) {
        $counts[$item['id_group']] = 0;
    }
    $counts[$item['id_group']] += 1;
}
//echo "<pre>";print_r($counts);exit;
//print_r($products);exit;


buildCategoryTree($categories, $counts);
echo "<pre>";print_r($categories);exit;
//buildCounts($categoryTree, $counts);

$categoryTreeRender = renderCategoryTree($categories, $categoryId);

$ids = buildTreeIds($categories, $categoryId);

$where = "";
if (!empty($ids)) {
    $where = "WHERE `id_group` IN (" . implode(",", $ids) . ")";
}
$sql = $pdo->prepare("SELECT * FROM `products` " . $where);
$sql->execute();
$rows = $sql->fetchAll();
$products = renderProducts($rows);

if ($categoryId) {
    echo $products;
    exit;
}
?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<table>
    <tr>
    </tr>
    <tr>
        <td>
            <a id='category' style='text-decoration:none; color:black;' href='#'>
                Category
            </a>
            <?= $categoryTreeRender ?>
        </td>
        <td>
            <a id='products' style='text-decoration:none; color:black;' href='#'>
                Products
            </a>
            <ul>
                <?= $products ?>
            </ul>
        </td>
    </tr>
</table>

<script>
    $(document).ready(function () {
        $('.child').next('ul').hide();

        $("body").on('click', "a", function (e) {
          //  if ($(this).next().find('ul').is(":visible")) {
            href = $(this);
            //console.log(href.attr('href'));
            $.ajax({
                url: typeof href.attr('href') === 'undefined' ? 0 : href.attr('href'),
                method: 'get',
                dataType: 'html',
                success: function (data) {
                    if (href.next('ul').is(":visible")) {
                        href.next('ul').hide()
                    } else {
                        href.next('ul').show();
                    }

                    $('#products').next('ul').html(data);
                }
            });

            e.stopPropagation();
            e.preventDefault();
        });
    });
</script>
