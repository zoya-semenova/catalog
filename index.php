<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
function buildCategoryTree(array $elements, $counts, $parentId = 0)
{
    $branch = [];

    foreach ($elements as $element) {
        $count = 0;
        if (!isset($element['count'])) {
            $element['count'] = 0;
        }

        if ($element['id_parent'] == $parentId) {

            $children = buildCategoryTree($elements, $counts, $element['id'], $count);
            if ($children) {
                $element['children'] = $children;
                //$element['count'] += count($children);
                //$count += (isset($counts[$element['id']]) ? $counts[$element['id']] : 0);

                foreach ($children as $child) {
                    //$count+=$child['count'];
                    $count += (isset($child['count']) ? $child['count'] : 0);
                }

            }
            //считаем количество товаров в категории
            $element['count'] += (isset($counts[$element['id']]) ? $counts[$element['id']] : 0);
            $element['count'] += $count;
            $branch[] = $element;
        }
    }

    return $branch;
}

function buildCategoryTreeIds(array $tree, $parentId = 0)
{
    $ids = [$parentId];

    foreach ($tree as $element) {
        if ($element['id_parent'] == $parentId) {
            $children = buildCategoryTreeIds($tree, $element['id']);
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
        <a  id=" . $category['id'] . " href = '/?group=" . $category['id'] . "'>" .
            $category['name'] . ' (' . $category['count'] . ')' . "</a>";
        if (!empty($category['children'])) {
            $html .= renderCategoryTree($category['children'], $category['id']);
        }
        $html .= '</li>';
    }
    $html .= '</ul>';

    return $html;
}

function renderProducts($products)
{
    $html = "";
    foreach ($products as $product) {
        $html .= '<li>' . $product['name'];
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

$categoriesTree = buildCategoryTree($categories, $counts);

$categoryTreeRender = renderCategoryTree($categoriesTree, $categoryId);

$ids = buildCategoryTreeIds($categories, $categoryId);

$where = "";
if (!empty($ids)) {
    $where = "WHERE `id_group` IN (" . implode(",", $ids) . ")";
}
$sql = $pdo->prepare("SELECT * FROM `products` " . $where);
$sql->execute();
$rows = $sql->fetchAll();
$products = renderProducts($rows);

//если пришел ajax отдаем участок html со списком продуктов
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

            //при клике на категорию запрашиваем список продуктов
            $.ajax({
                url: typeof href.attr('href') === 'undefined' ? 0 : href.attr('href'),
                method: 'get',
                dataType: 'html',
                success: function (data) {
                    //если категория открыта - закрываем, и наоборот
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
