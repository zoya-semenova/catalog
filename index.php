<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
function buildCategoryTree(array $elements, $parentId = 0)
{
    $branch = [];

    foreach ($elements as $element) {
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

function buildCounts($elements, $counts, $parentId = 0)
{
    $branch = [];

    foreach ($elements as &$element) {
        if ($element['id_parent'] == $parentId) {
            $children = buildCounts($elements, $element['id']);

            if (!isset($element['count'])) {
                $element['count'] = 0;
            }
            $element['count'] += $counts[$element['id']];

            $branch[] = $element;
        }
    }

    return $branch;
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


//print_r($products);exit;
$categories = buildCategoryTree($categories);
//$categoryTree = buildCounts($categories, $counts);
function count_children(&$categories) {
    $count = 0;
    foreach ($categories as &$child) {
        $count++;
        if (isset($child['children']) && is_array($child['children'])) {
            $count += count_children($child['children']);
        }
        $child['count'] = $count;
    }
    return $count;
}
count_children($categories);
echo "<pre>";print_r($categories);exit;
//$categoryTree =

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
