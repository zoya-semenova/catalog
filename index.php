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
<a  id=" . $category['id'] . " href = '/?group=" . $category['id'] . "'>" . $category['name'] . "</a>";
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
$rows = $sql->fetchAll();

$categoryTree = buildCategoryTree($rows);

$categoryTreeRender = renderCategoryTree($categoryTree, $categoryId);

$ids = buildTreeIds($rows, $categoryId);

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
                <span id='category-symbol'>+ </span>
                Category
            </a>
            <?= $categoryTreeRender ?>
        </td>
        <td>
            <a id='products' style='text-decoration:none; color:black;' href='#'>
                <span id='products-symbol'>+ </span>
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
