# Концепция

Предполагается, что все основные сущности (Страницы, Категории, Продукты) будут построены на единой базе.
Существует 3 основных таблицы, хранящих структуру всех сущностей:
 
 - `dotplant_structure` - хранит древовидную иерархию сущностей,
 - `dotplant_entity` - хранит имя класса и название сущности,
 - `dotplant_structure_translation` - хранит основные поля закголовков и метатегов, с переводами на языки, 
 настроенные в системе
 
 При создании и добавлении новой сущности помимо этих таблиц, создаются таблицы для хранения свойств. 
 Таблицы именуются в зависимости от заданного префикса. Например, для сущности Страница таблицы будут иметь префикс
 `dotplant_page`

## Описание и пример использования

# Описание

Данное расширение - базовая структура для всех сущностей, которые есть и будут добавляться в CMS DotPlant3.
Расширение включает в себя:

 - набор миграций, создающих основные таблицы `dotplant_structure`, `dotplant_entity`, `dotplant_structure_translation`; 
 - набор базовых экшенов, которые предоставляют функционал создания, редактирования, удаления, 
 сборки данных для `jstree` дерева и поиска с автодополнением по базовым полям сущностей;
 - набор файлов представления по умолчанию для отображения и редактирвоания сущностей;
 
### Настройки расшерения

Данное расширение имеет тип `dotplant-extension`, благодаря этому оно имеет ряд собственных настроек, редактируемых 
через административную часть сайта в разделе `/extensions-manager/extensions/config`. Имеются 2 базовых настройки

- *Элементов на странице по умолчанию*. Количество элементов на страницу в списке сущностей на странице их редактирования
- *Показывать скрытые записи в дереве*. Если для отображения списка сущностей используется виджет 
`devgroup\JsTreeWidget\widgets\TreeWidget` можно настроить скрывание или отображение спрятанных записей.

Данные настройки могут наследоваться сущностями, создаваемыми на базе данного расширения. Если настройки не наследовать, 
будут использованы настройки базового расширения.

# Как использовать

Перед созданием расширения необходимо:

 - ознакомиться с [правилами оформления кода](https://github.com/DevGroup-ru/code-style)
 - ознакомиться с [правилами создания расширений для DotPlant](https://github.com/DevGroup-ru/yii2-extensions-manager/blob/master/docs/ru/package-creation.md)
 - склонировать [репозиторий с примером расширения](https://github.com/DevGroup-ru/dotplant-extension-demo)
  
Живой пример использования можно посмотреть в [расширении](https://github.com/DevGroup-ru/dotplant-content), 
реализующем функционал обычных страниц.

Здесь мы не будем рассматривать создание конфигурационной модели расширения, файлов представления конфигурации и создания правильного файла `composer.json` для расширения типа `dotplant-extension`. Все это описано в [правилах создания расширений для DotPlant](https://github.com/DevGroup-ru/yii2-extensions-manager/blob/master/docs/ru/package-creation.md)

К примеру, реализуем сущность `Ticket` - какой-то билет

Прежде всего, необходимо создать соответствующую модель и унаследовать ее от модели 
`DotPlant\EntityStructure\models\BaseStructure`

```php 
class Ticket extends BaseStructure
{
    use EntityTrait;
    use BaseActionsInfoTrait;
    use SoftDeleteTrait;

    const TRANSLATION_CATEGORY = 'dotplant.tickets';

    /**
     * @inheritdoc
     */
    protected static $tablePrefix = 'dotplant_tickets';

    /**
     * @inheritdoc
     */
    protected static function getPageSize()
    {
        return TicketsModule::module()->itemsPerPage;
    }
}
```

Данного кода уже вполне достаточно, чтобы модель получила доступ к структуре данных.
Рассмотрим детальнее все, что там написано.

*Блок трейтов*
```php
    use EntityTrait;
    use BaseActionsInfoTrait;
    use SoftDeleteTrait;
```

Эти трейты поставляются с расширением [yii2-entity](https://github.com/DevGroup-ru/yii2-entity), которое призвано упростить
создание и работу с обычными SEO полями (h1, title, slug, url и т.д.), полями для хранения даты создания и модификации записи
и полем для "мягкого удаления" записей. Сущности наследую все эти поля от базовой модели, так что необходимо подключить эти трейты.

*Переводы*
```php
    const TRANSLATION_CATEGORY = 'dotplant.tickets';
```
Категория переводов для создаваемого расширения. Эта константа нужня для того, чтобы базовые экшены могли правильно 
перевести название сущности, с которой они работают. При этом необходимо не забыть добавить в файл переводов перевод для имени создаваемой сущности

```php
return [
    ...
    'Ticket' => 'Билет',
    ...
];
```

*Префикс таблиц*
```php
    /**
     * @inheritdoc
     */
    protected static $tablePrefix = 'dotplant_tickets';
```   
К сущностям, создаваемым на базе `dotplant\entity-structure` автоматически подключается расширение для работы со свойствами [yii2-data-structure-tools](https://github.com/DevGroup-ru/yii2-data-structure-tools). И для того, чтобы каждая сущность хранила свои свойства в определенных таблицах, необходимо указать префикс таблиц.

*Настройка количества записей на странице в админке*

```php
    /**
     * @inheritdoc
     */
    protected static function getPageSize()
    {
        return TicketsModule::module()->itemsPerPage;
    }
```

При желании, можно (и нужно, если записями сущности нужно управлять из админки) создать модуль расширения, об этом подробннее в [правилах создания расширений для DotPlant](https://github.com/DevGroup-ru/yii2-extensions-manager/blob/master/docs/ru/package-creation.md). Если не перегрузить этот метод в своей модели, то будет использован метод из родительской и настройки расширения, указанные выше, будут действовать и на расширение которое мы создаем.

Далее, чтобы модель заработала и создались необходимые дополнительные таблицы, нужно создать миграцию, примерно следующего содержания.
```php
public function up()
    {
        /*
        Проверяем, была ли создана таблица для хранения сущностей. Если нет, значит родительское расширение не было
        установлено или активировано. Необходимо прежде сделать это
        */
        if (null === $this->db->getTableSchema(Entity::tableName())) {
            Console::stderr("Please, first install if not and activate 'DotPlant Entity Structure' extension!" . PHP_EOL);
            return false;
        }
        /* Добавляем запись в таблицу сущностей */
        $this->insert(
            Entity::tableName(),
            [
                'name' => 'Ticket',
                'class_name' => Ticket::class
            ]
        );
        /* Генерируем все необходимые таблицы для хранения свойств */
        PropertiesTableGenerator::getInstance()->generate(Ticket::class);
        /* 
        Создаем роль для системы контроля доступа, чтобы только пользователи с соответствующей ролью могли управлять записями создаваемой сущности 
        */
        $auth = Yii::$app->authManager;
        $permission = $auth->createPermission('tickets-manage');
        $permission->description = 'Tickets manage permission';
        $auth->add($permission);
    }

    public function down()
    {
        $this->delete(
            Entity::tableName(),
            [
                'name' => 'Ticket',
                'class_name' => Ticket::class
            ]
        );
        PropertiesTableGenerator::getInstance()->drop(Ticket::class);
        $auth = Yii::$app->authManager;
        $permission = $auth->getPermission('tickets-manage');
        if (null !== $permission) {
            $auth->remove($permission);
        }
    }
```
Следует помнить, что руками данную миграцию выполнять не нужно! Она будет выполнена автоматически при активации расширения в менеджере расширений.





