#!/usr/bin/env python3
"""
Скрипт для реорганизации SVG файла по слоям и очистки
"""
import xml.etree.ElementTree as ET
import re
from copy import deepcopy

# Регистрируем пространство имен
ET.register_namespace('', 'http://www.w3.org/2000/svg')
NS = {'svg': 'http://www.w3.org/2000/svg'}

def clean_attributes(elem):
    """Удаляет sodipodi, inkscape и другие мусорные атрибуты"""
    attrs_to_remove = []
    for attr in list(elem.attrib.keys()):
        if attr.startswith('sodipodi:') or attr.startswith('inkscape:'):
            attrs_to_remove.append(attr)
        elif attr == 'xmlns:svg':
            attrs_to_remove.append(attr)
    
    for attr in attrs_to_remove:
        del elem.attrib[attr]
    
    # Удаляем пустые transform
    if 'transform' in elem.attrib:
        transform = elem.attrib['transform'].strip()
        if not transform or transform in ['translate(0,0)', 'matrix(1,0,0,1,0,0)']:
            del elem.attrib['transform']
    
    # Рекурсивно обрабатываем дочерние элементы
    for child in elem:
        clean_attributes(child)

def normalize_text_element(text_elem):
    """Нормализует элемент text, используя tspan с точными координатами"""
    if text_elem.tag != '{http://www.w3.org/2000/svg}text':
        return
    
    # Сохраняем базовые координаты
    base_x = text_elem.get('x', '0')
    base_y = text_elem.get('y', '0')
    
    # Собираем все tspan (только прямые дочерние, не вложенные)
    direct_tspans = [child for child in text_elem if child.tag == '{http://www.w3.org/2000/svg}tspan']
    
    if not direct_tspans:
        # Если нет tspan, но есть прямой текст
        if text_elem.text and text_elem.text.strip():
            tspan = ET.SubElement(text_elem, 'tspan')
            tspan.set('x', base_x)
            tspan.set('y', base_y)
            tspan.text = text_elem.text.strip()
            text_elem.text = None
        return
    
    # Обрабатываем существующие tspan, сохраняя их структуру
    prev_y = None
    first_tspan = True
    
    for tspan in direct_tspans:
        # Получаем координаты tspan
        tspan_x = tspan.get('x', base_x)
        tspan_y = tspan.get('y', base_y)
        
        # Устанавливаем x (сохраняем оригинальное значение)
        if 'x' not in tspan.attrib:
            tspan.set('x', tspan_x)
        
        # Вычисляем dy для правильного межстрочного интервала
        if first_tspan:
            if 'y' not in tspan.attrib:
                tspan.set('y', tspan_y)
            prev_y = float(tspan_y)
            first_tspan = False
        else:
            if prev_y is not None:
                dy = float(tspan_y) - prev_y
                if abs(dy) > 0.001:  # Только если есть реальное смещение
                    # Используем dy вместо y для последующих строк
                    if 'y' in tspan.attrib:
                        del tspan.attrib['y']
                    tspan.set('dy', str(dy))
                prev_y = float(tspan_y)
        
        # Удаляем пустые tspan (без текста и без дочерних элементов)
        has_text = (tspan.text or '').strip()
        has_children = len([c for c in tspan if c.tag == '{http://www.w3.org/2000/svg}tspan' and (c.text or '').strip()])
        if not has_text and not has_children:
            text_elem.remove(tspan)

def normalize_all_text(root):
    """Нормализует все текстовые элементы"""
    for text_elem in root.iter('{http://www.w3.org/2000/svg}text'):
        normalize_text_element(text_elem)

def categorize_elements(layer1):
    """Категоризирует элементы по слоям"""
    categories = {
        'background': [],
        'balance_tank': [],
        'balance_left_text': [],
        'levels_column': [],
        'full_table': [],
        'other': []
    }
    
    # Собираем все элементы
    all_elements = list(layer1)
    
    for elem in all_elements:
        elem_id = elem.get('id', '')
        
        # Бак баланса: rect62 (контур), rect63 (внутренний прямоугольник), тексты с цифрами
        if elem_id in ['rect62', 'rect63']:
            categories['balance_tank'].append(elem)
            continue
        
        # Цифры и заголовки бака баланса
        if elem_id in ['text138', 'text139', 'text140', 'text141', 'text142', 'text144']:
            categories['balance_tank'].append(elem)
            continue
        
        # Левый текст (описания)
        if elem_id in ['text189', 'text195', 'text199', 'text204']:
            categories['balance_left_text'].append(elem)
            continue
        
        # Колонка уровней (Premium, Sinbad Standard, etc.)
        if elem_id in ['text128', 'text131', 'text133', 'text135', 'rect135', 'rect136', 'rect137']:
            categories['levels_column'].append(elem)
            continue
        
        # Таблица: ячейки (rect45-rect124) и цены (text100-text187, text166-text186)
        # Исключаем rect65, rect66, rect113-124, которые являются разделителями строк
        if elem_id.startswith('rect') and elem_id[4:].isdigit():
            rect_num = int(elem_id[4:])
            if 45 <= rect_num <= 124:
                # rect65, rect66, rect113-124 - это разделители строк таблицы, они тоже часть таблицы
                categories['full_table'].append(elem)
                continue
        
        if elem_id.startswith('text') and elem_id[4:].isdigit():
            text_num = int(elem_id[4:])
            # Цены в таблице и заголовки колонок
            if (88 <= text_num <= 187) or (166 <= text_num <= 186):
                # Проверяем, не является ли это текстом бака или левым текстом
                if elem_id not in ['text138', 'text139', 'text140', 'text141', 'text142', 'text144',
                                   'text189', 'text195', 'text199', 'text204', 'text128', 'text131', 
                                   'text133', 'text135', 'text145']:
                    categories['full_table'].append(elem)
                    continue
        
        # text145 - скрытый текст (opacity:0), может быть в Other
        if elem_id == 'text145':
            categories['other'].append(elem)
            continue
        
        # text159 - текст вне видимой области (y=100), может быть в Other
        if elem_id == 'text159':
            categories['other'].append(elem)
            continue
        
        # Фоновые элементы (декоративные прямоугольники вне таблицы)
        # В данном SVG фоновых элементов нет, все прямоугольники относятся к таблице или другим слоям
        
        # Все остальное
        categories['other'].append(elem)
    
    return categories

def create_layered_svg(input_file, output_file):
    """Создает реорганизованный SVG с слоями"""
    tree = ET.parse(input_file)
    root = tree.getroot()
    
    # Очищаем атрибуты
    clean_attributes(root)
    
    # Нормализуем текст
    normalize_all_text(root)
    
    # Находим или создаем layer1
    layer1 = root.find('.//{http://www.w3.org/2000/svg}g[@id="layer1"]')
    if layer1 is None:
        # Если нет layer1, создаем его и перемещаем все элементы туда
        layer1 = ET.SubElement(root, 'g')
        layer1.set('id', 'layer1')
        for child in list(root):
            if child.tag == '{http://www.w3.org/2000/svg}defs':
                continue
            if child != layer1:
                layer1.append(child)
    
    # Категоризируем элементы
    categories = categorize_elements(layer1)
    
    # Удаляем все элементы из layer1
    for elem in list(layer1):
        layer1.remove(elem)
    
    # Создаем слои
    layer_order = [
        ('Layer_Background', categories['background']),
        ('Layer_BalanceTank', categories['balance_tank']),
        ('Layer_BalanceLeftText', categories['balance_left_text']),
        ('Layer_LevelsColumn', categories['levels_column']),
        ('Layer_FullTable', categories['full_table']),
        ('Layer_Other', categories['other'])
    ]
    
    for layer_name, elements in layer_order:
        if not elements:
            continue
        
        layer_group = ET.SubElement(layer1, 'g')
        layer_group.set('id', layer_name)
        
        # Для Layer_BalanceTank создаем подгруппы
        if layer_name == 'Layer_BalanceTank':
            tank_outline = ET.SubElement(layer_group, 'g')
            tank_outline.set('id', 'Tank_Outline')
            
            tank_sections = ET.SubElement(layer_group, 'g')
            tank_sections.set('id', 'Tank_Sections')
            
            tank_labels = ET.SubElement(layer_group, 'g')
            tank_labels.set('id', 'Tank_Labels')
            
            # Распределяем элементы по подгруппам
            for elem in elements:
                elem_id = elem.get('id', '')
                if elem_id == 'rect62':  # Контур бака
                    tank_outline.append(elem)
                elif elem_id == 'rect63':  # Внутренние формы
                    tank_sections.append(elem)
                elif elem_id in ['text138', 'text139', 'text140', 'text141', 'text142', 'text144']:  # Цифры и заголовки
                    tank_labels.append(elem)
                else:
                    tank_sections.append(elem)
        else:
            # Для остальных слоев просто добавляем элементы
            for elem in elements:
                layer_group.append(elem)
    
    # Удаляем пустые группы
    def remove_empty_groups(elem):
        for child in list(elem):
            remove_empty_groups(child)
            if child.tag == '{http://www.w3.org/2000/svg}g' and len(child) == 0:
                elem.remove(child)
    
    remove_empty_groups(root)
    
    # Сохраняем результат с правильным форматированием
    tree.write(output_file, encoding='utf-8', xml_declaration=True)
    
    print(f"Обработка завершена. Результат сохранен в {output_file}")
    print(f"Статистика:")
    for layer_name, elements in layer_order:
        print(f"  {layer_name}: {len(elements)} элементов")

if __name__ == '__main__':
    import sys
    input_file = sys.argv[1] if len(sys.argv) > 1 else '/var/www/gradaeronaut.com/shape-sinbad/privileges_list_1.svg'
    output_file = sys.argv[2] if len(sys.argv) > 2 else '/var/www/gradaeronaut.com/shape-sinbad/privileges_list_cleaned.svg'
    
    create_layered_svg(input_file, output_file)


