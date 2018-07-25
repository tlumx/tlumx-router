<?php

return  [
  'routes' =>
   [
    'foo' =>
     [
      'datas' =>
       [
        0 =>
         [
          0 => '/foo',
         ],
       ],
      'methods' =>
       [
        0 => 'GET',
        1 => 'POST',
       ],
      'pattern' => '/foo',
      'middlewares' =>
       [
        0 => 'midd1',
        1 => 'midd2',
       ],
      'handler' =>
       [
        '_controller' => 'home',
        '_action' => 'index',
       ],
      'group' => null,
     ],
    'article' =>
     [
      'datas' =>
       [
        0 =>
         [
          0 => '/admin/articles/',
          1 =>
           [
            0 => 'id',
            1 => '\\d+',
           ],
         ],
        1 =>
         [
          0 => '/admin/articles/',
          1 =>
           [
            0 => 'id',
            1 => '\\d+',
           ],
          2 => '/',
          3 =>
           [
            0 => 'title',
            1 => '[^/]+',
           ],
         ],
       ],
      'methods' =>
       [
        0 => 'GET',
       ],
      'pattern' => '/admin/articles/{id:\\d+}[/{title}]',
      'middlewares' =>
       [
        0 => 'adm_midd1',
        1 => 'adm_midd2',
        2 => 'midd1',
        3 => 'midd2',
       ],
      'handler' =>
       [
        0 => 'article_handler',
       ],
      'group' => 'adm',
     ],
   ],
  'dispatch_data' =>
   [
    0 =>
     [
      'GET' =>
       [
        '/foo' => 'foo',
       ],
      'POST' =>
       [
        '/foo' => 'foo',
       ],
     ],
    1 =>
     [
      'GET' =>
       [
        0 =>
         [
          'regex' => '~^(?|/admin/articles/(\\d+)|/admin/articles/(\\d+)/([^/]+))$~',
          'routeMap' =>
           [
            2 =>
             [
              0 => 'article',
              1 =>
               [
                'id' => 'id',
               ],
             ],
            3 =>
             [
              0 => 'article',
              1 =>
               [
                'id' => 'id',
                'title' => 'title',
               ],
             ],
           ],
         ],
       ],
     ],
   ],
];
