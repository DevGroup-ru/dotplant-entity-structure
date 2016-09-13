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
 - базовый контроллер, который предоставляет доступ к базовым экшенам по маршрутам:
   * `/structure/entity-manage/index` - дерево всех сущностей и список всех сущностей, находящихся внутри выбранной в дереве
   * `/structure/entity-manage/edit?id="id"&entity_id="id"` - редактирование конкретной записи. `id` и `entity_id` - обязательные параметры
   * `/structure/entity-manage/delete?id="id"&entity_id="id"` - удаление конкретной записи. `id` и `entity_id` - обязательные параметры  
 - набор базовых экшенов, которые предоставляют функционал создания, редактирования, удаления, 
 сборки данных для `jstree` дерева и поиска с автодополнением по базовым полям сущностей.
 - набор файлов представления по умолчанию для отображения и редактирвоания сущностей;
 
### Настройки расширения

Данное расширение имеет тип `dotplant-extension`, благодаря этому оно имеет ряд собственных настроек, редактируемых 
через административную часть сайта в разделе `/extensions-manager/extensions/config`. Имеются 2 базовых настройки

- *Элементов на странице по умолчанию*. Количество элементов на страницу в списке сущностей на странице их редактирования
- *Показывать скрытые записи в дереве*. Если для отображения списка сущностей используется виджет 
`devgroup\JsTreeWidget\widgets\TreeWidget` можно настроить скрывание или отображение спрятанных записей.

Данные настройки могут переопределяться сущностями, создаваемыми на базе данного расширения. Если настройки не переопределять, 
будут использованы настройки базового расширения.

# Как использовать

Рассмотрим небольшой пример создания новой сущности на базе расширения `dotplant/entity-structure`.

Перед созданием расширения необходимо:

 - ознакомиться с [правилами оформления кода](https://github.com/DevGroup-ru/code-style)
 - ознакомиться с [правилами создания расширений для DotPlant](https://github.com/DevGroup-ru/yii2-extensions-manager/blob/master/docs/ru/package-creation.md)
 - склонировать [репозиторий с примером расширения](https://github.com/DevGroup-ru/dotplant-extension-demo)
  
Живой пример использования можно посмотреть в [расширении](https://github.com/DevGroup-ru/dotplant-content), 
реализующем функционал обычных страниц.

Здесь мы не будем рассматривать создание конфигурационной модели расширения, файлов представления конфигурации и 
создания правильного файла `composer.json` для расширения типа `dotplant-extension`. Все это описано 
в [правилах создания расширений для DotPlant](https://github.com/DevGroup-ru/yii2-extensions-manager/blob/master/docs/ru/package-creation.md)

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

    protected static $tablePrefix = 'dotplant_tickets';

    protected static function getPageSize()
    {
        return TicketsModule::module()->itemsPerPage;
    }
    
     public static function getAccessRules()
    {
        return [
            'view' => 'dotplant-tickets-view',
            'edit' => 'dotplant-tickets-edit',
            'delete' => 'dotplant-tickets-delete',
            'apply' => 'dotplant-tickets-apply',
        ];
    }
    
    protected static $injectionActions = [
        'apply' => [
            'class' => TicketsApplyAction::class
        ],
    ];

    public function getEditPageTitle()
    {
        return (true === $this->getIsNewRecord())
            ? Yii::t('dotplant.tickets', 'New ticket')
            : Yii::t('dotplant.tickets', 'Edit {title}', ['title' => $this->name]);
    }

    public static function getModuleBreadCrumbs()
    {
        return [
            [
                'url' => ['/structure/entity-manage/index'],
                'label' => Yii::t('dotplant.tickets', 'Tickets management')
            ]
        ];
    }

    public function additionalGridButtons()
    {
        return [
            'apply' => [
                'url' => '/structure/entity-manage/apply',
                'icon' => 'check-square-o',
                'class' => 'btn-primary',
                'label' => Yii::t('dotplant.tickets', 'Apply'),
                'attrs' => ['entity_id']
            ]
        ];
    }
    
    public static function jsTreeContextMenuActions()
    {
        $ticketsEntityId = Entity::getEntityIdForClass(self::class);
        return [
            'tickets' => [
                'label' => Yii::t('dotplant.tickets', 'Apply ticket'),
                'action' => ContextMenuHelper::actionUrl(['/structure/entity-manage/apply']),
                'showWhen' => ['entity_id' => $ticketsEntityId]
            ],
        ];
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
и полем для "мягкого удаления" записей. Сущности наследуют все эти поля от базовой модели, так что необходимо подключить эти трейты.

*Переводы*

```php
    const TRANSLATION_CATEGORY = 'dotplant.tickets';
```
Категория переводов для создаваемого расширения. Эта константа нужня для того, чтобы базовые экшены могли правильно 
перевести название сущности, с которой они работают. При этом необходимо не забыть добавить в файл переводов перевод 
для имени создаваемой сущности

```php
return [
    ...
    'Ticket' => 'Билет',
    ...
];
```

*Префикс таблиц*

```php
    protected static $tablePrefix = 'dotplant_tickets';
```   
К сущностям, создаваемым на базе `dotplant\entity-structure` автоматически подключается расширение для работы со свойствами [yii2-data-structure-tools](https://github.com/DevGroup-ru/yii2-data-structure-tools). И для того, чтобы каждая сущность хранила свои свойства в определенных таблицах, необходимо указать префикс таблиц.

*Настройка количества записей на странице в админке*

```php
    protected static function getPageSize()
    {
        return TicketsModule::module()->itemsPerPage;
    }
```

*Права доступа к экшенам расширения*

```php
    public static function getAccessRules()
    {
        return [
            'view' => 'dotplant-tickets-view',
            'edit' => 'dotplant-tickets-edit',
            'delete' => 'dotplant-tickets-delete',
            'apply' => 'dotplant-tickets-apply',
        ];
    }
```    

Если расширение имеет особые разрешения на выполняемые действия, то их следует задавать, как описано выше. Метод дожен возвращать массив,
где ключ - название выполняемого действия, а значение - название разрешения для выполнения этого действия.
По умолчанию обрабатываются разрешения только для действий `view`, `edit` и `delete`. Если вы планируете добавлять собственные экшены,
то специальные разрешения для них можно задать там же, как к примеру `'apply' => 'dotplant-tickets-apply'`. А в экшене это необходимо 
отдельно обработать. 
 
*Встраивание собственных экшенов в общий контрлоллер*

```php
    protected static $injectionActions = [
        'apply' => [
            'class' => TicketsApplyAction::class
        ],
    ];
```

Если требуется добавить в контроллер собственные действия, то добиться этого можно, как в примере выше. Дополнительных экшенов может быть несколько.
Этот массив имеет абсолютно аналогичную структуру, как и массив, который возвращает метод  `yii\web\Controller::actions()` и работает по такому же принципу.
После добавления, экшн будет доступен по маршруту `/structure/entity-manage/apply`

*Кастомизация заголовков и хлебных крошек*

```php
    public function getEditPageTitle()
    {
        return (true === $this->getIsNewRecord())
            ? Yii::t('dotplant.tickets', 'New ticket')
            : Yii::t('dotplant.tickets', 'Edit {title}', ['title' => $this->name]);
    }

    public static function getModuleBreadCrumbs()
    {
        return [
            [
                'url' => ['/structure/entity-manage/index'],
                'label' => Yii::t('dotplant.tickets', 'Tickets management')
            ]
        ];
    }
```            

Если есть необходимость сделать уникальные заголовки при редактировании страницы создваемой сущности или изменить массив хлебных крошек,
это можно сделать с помощью методов выше.

*Дополнительные кнопки в таблице сущностей*

```php
    public function additionalGridButtons()
    {
        return [
            'apply' => [
                'url' => '/structure/entity-manage/apply',
                'icon' => 'check-square-o',
                'class' => 'btn-primary',
                'label' => Yii::t('dotplant.tickets', 'Apply'),
                'attrs' => ['entity_id']
            ]
        ];
    }
```

С помощью метода, указанного выше, есть возможность добавить собственные кнопки в список сущностей, отображаемых на страницах листинга.
При этом, напротив каждой записи с создаваемой сущностью в списке кроме стандартных кнопок "Редактировать" и "Удалить" появится кнопка 
"Apply", нажатие на которую будет приводить к вызову экшена, указанного в параметре `url`. В случае выше - это собственный экшн, который был
внедрен в базовый контроллер.

*Добавление собственных ссылок в контекстное меню дерева*

```php
    public static function jsTreeContextMenuActions()
    {
        $ticketsEntityId = Entity::getEntityIdForClass(self::class);
        return [
            'tickets' => [
                'label' => Yii::t('dotplant.tickets', 'Apply ticket'),
                'action' => ContextMenuHelper::actionUrl(['/structure/entity-manage/apply']),
                'showWhen' => ['entity_id' => $ticketsEntityId]
            ],
        ];
    }
```

На странице листинга сущностей выведено дерево всех сущностей с помощью расширения [yii2-jstree-widget](https://github.com/DevGroup-ru/yii2-jstree-widget).
Расширение использует плагин [jstree](https://www.jstree.com/) , который позволяет настраивать добавление контекстного меню при правом клике мышкой на 
элементе дерева. По умолчанию есть только 2 пункта "Открыть" и "Редактировать". С помощью метода, показанного выше, можно добавить
собственные пункты меню, которые будут показываться в зависимости от определенных уловий. За данную возможность отвечает ключ:
`'showWhen' => ['entity_id' => $ticketsEntityId]` 
В данном случае проверяется, что сущность, для которой мы хотим добавить пункт, является сущностью Ticket. Если не указать условие, дополнительные пункты
будут доступны для всех элементов дерева.
Ключ массива, описывающего пункт меню - `tickets` следует задавать только латинскими буквами без пробелов и спецсимволов. В противном случае возникнет ошибка при 
при попытке вызвать контекстное меню.


Далее, чтобы модель заработала и создались необходимые дополнительные таблицы, нужно создать миграцию, примерно следующего содержания.

```php
private static $permissionsConfig = [
     'TicketsManager' => [
         'descr' => 'Tickets Management Role',
         'permits' => [
             'dotplant-tickets-view' => 'Viewing Tickets',
             'dotplant-tickets-edit' => 'Editing Tickets',
         ]
     ],
     'TicketsAdministrator' => [
         'descr' => 'Tickets Administrator',
         'permits' => [
             'dotplant-tickets-delete' => 'Deleting Tickets'
         ],
         'roles' => [
             'TicketsManager'
         ],
     ],
    ];

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
        \app\helpers\PermissionsHelper::createPermissions(self::$permissionsConfig);
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
        \app\helpers\PermissionsHelper::removePermissions(self::$permissionsConfig);
    }
```
Следует помнить, что руками данную миграцию выполнять не нужно! Она будет выполнена автоматически при активации расширения в менеджере расширений.

*Пример реализации собственного экшена*

```php
class TicketsApplyAction extends BaseAdminAction
{
    /**
     * @param $id
     * @param $entity_id
     * @param string $returnUrl
     * @return \yii\web\Response
     * @throws ForbiddenHttpException
     * @throws \Exception
     * @throws bool
     */
    public function run($id, $entity_id, $returnUrl = '')
    {
        //получаем класс сущности по её id из запроса
        /** @var BaseStructure $entityClass */
        $entityClass = Entity::getEntityClassForId($entity_id);
        
        //получаем массив прав доступа для сущности
        $permissions = $entityClass::getAccessRules();
        
        //проверяем, имеет ли пользователь соответствующее разрешение
        if (true === isset($permissions['apply']) && false === Yii::$app->user->can($permissions['apply'])) {
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
        }
        
        //выполняем необходимые действия
        
        //возвращаем пользователя на страницу, с которой он пришел, если необходимо
        $returnUrl = empty($returnUrl) ? ['/structure/entity-manage'] : $returnUrl;
        return $this->controller->redirect($returnUrl);
    }
}
```

