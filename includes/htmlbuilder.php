<?php
/*----------------------------------------------------------------*/
	
	class HTMLBuilder {
		protected $document;
		protected $element;
		
		public function __construct() {
			$doctype = DOMImplementation::createDocumentType(
				'html',
				'-//W3C//DTD HTML 4.01//EN',
				'http://www.w3.org/TR/html4/strict.dtd'
			);
			$this->document = DOMImplementation::createDocument(
				null, null, $doctype
			);
			$this->document->recover = true;
			$this->document->formatOutput = true;
			$this->element = $this->document;
		}
		
		public function __call($name, $arguments) {
			$parent = $this->element;
			$element = $this->document->createElement($name);
			$this->element = $element;
			$parent->appendChild($this->element);
			
			$child = new HTMLBuilderElement($this);
			
			foreach ($arguments as $argument) {
				if (!$argument instanceof Closure) continue;
				
				$argument($child);
			}
			
			$this->element = $parent;
			
			return $child;
		}
		
		public function __get($name) {
			switch ($name) {
				case 'attribute':
					return new HTMLBuilderAttributeIterator($this);
					
				case 'child':
					return new HTMLBuilderElementIterator($this);
					
				case 'class':
					return new HTMLBuilderClassIterator($this);
					
				case 'name':
					return $this->element->name;
					
				case 'value':
					$value = '';
					
					foreach ($this->element->childNodes as $child) {
						$value .= $this->document->saveXML($child);
					}
					
					return $value;
					
				default:
					throw new Exception(sprintf(
						"Property '%s' does not exist.", $name
					));
			}
		}
		
		public function __set($name, $value) {
			switch ($name) {
				case 'value':
					$fragment = $this->document->createDocumentFragment();
					$fragment->appendXML($value);
					
					foreach ($this->element->childNodes as $child) {
						$this->element->removeChild($child);
					}
					
					$this->element->appendChild($fragment);
					$this->document->normalizeDocument();
					
					return $this;
					
				case 'attribute':
				case 'child':
				case 'class':
				case 'name':
					throw new Exception(sprintf(
						"Property '%s' is read only.", $name
					));
					
				default:
					throw new Exception(sprintf(
						"Property '%s' does not exist.", $name
					));
			}
		}
		
		public function __toString() {
			$value = '';
			
			if ($this->element instanceof DOMDocument) {
				foreach ($this->element->childNodes as $child) {
					$value .= $this->document->saveXML($child);
				}
			}
			
			else {
				$value = $this->document->saveXML($this->element);
			}
			
			return $value;
		}
		
		protected function document() {
			return $this->document;
		}
		
		protected function element(DOMNode $element = null) {
			if ($element) {
				$this->element = $element;
			}
			
			return $this->element;
		}
	}
	
/*----------------------------------------------------------------*/
	
	class HTMLBuilderAttribute extends HTMLBuilder {
		protected $document;
		protected $element;
		
		public function __construct(HTMLBuilder $builder) {
			$this->document = $builder->document();
			$this->element = $builder->element();
		}
		
		public function __get($name) {
			switch ($name) {
				case 'name':
					return $this->element()->name;
					
				case 'value':
					return $this->element()->value;
					
				default:
					throw new Exception(sprintf(
						"Property '%s' does not exist.", $name
					));
			}
		}
		
		public function __set($name, $value) {
			switch ($name) {
				case 'name':
					throw new Exception(sprintf(
						"Property '%s' is read only.", $name
					));
					
				case 'value':
					$this->element()->value = $value;
					
					return $this;
					
				default:
					throw new Exception(sprintf(
						"Property '%s' does not exist.", $name
					));
			}
		}
	}
	
	class HTMLBuilderAttributeIterator extends HTMLBuilder implements Iterator, Countable {
		protected $builder;
		protected $document;
		protected $element;
		protected $index;
		protected $list;
		
		public function __construct(HTMLBuilder $builder) {
			$this->builder = $builder;
			$this->document = $builder->document();
			$this->element = $builder->element();
			$this->list = $this->element->attributes;
		}
		
		public function __get($name) {
			$node = $this->element->getAttributeNode($name);
			
			if (!$node) {
				$this->element->setAttribute($name, '');
				$node = $this->element->getAttributeNode($name);
			}
			
			$element = $this->builder->element();
			$this->builder->element($node);
			$node = new HTMLBuilderAttribute($this->builder);
			$this->builder->element($element);
			
			return $node;
		}
		
		public function __set($name, $value) {
			$this->element->setAttribute($name, $value);
		}
		
		public function __toString() {
			$value = '';
			
			foreach ($this->list as $node) {
				$value .= $this->document->saveXML($node);
			}
			
			return $value;
		}
		
		public function rewind() {
			$this->index = 0;
		}
		
		public function count() {
			return $this->list->length;
		}
		
		public function current() {
			$node = $this->list->item($this->index);
			$element = $this->builder->element();
			$this->builder->element($node);
			$node = new HTMLBuilderAttribute($this->builder);
			$this->builder->element($element);
			
			return $node;
		}
		
		public function key() {
			return $this->index;
		}
		
		public function next() {
			$this->index++;
		}
		
		public function valid() {
			return $this->list->item($this->index);
		}
	}
	
/*----------------------------------------------------------------*/
	
	class HTMLBuilderClassIterator extends HTMLBuilder {
		protected $document;
		protected $element;
		protected $classes;
		
		public function __construct(HTMLBuilder $builder) {
			$this->document = $builder->document();
			$this->element = $builder->element();
			$classes = $this->element->getAttribute('class');
			$classes = preg_split('%\s+%', $classes, 0, PREG_SPLIT_NO_EMPTY);
			
			$this->classes = $classes;
		}
		
		public function __toString() {
			return $this->element->getAttribute('class');
		}
		
		public function add($value) {
			$values = preg_split('%\s+%', $value, 0, PREG_SPLIT_NO_EMPTY);
			
			foreach ($values as $value) {
				if (in_array($value, $this->classes)) continue;
				
				$this->classes[] = $value;
			}
			
			$this->element->setAttribute('class', implode(' ', $this->classes));
			
			return $this;
		}
		
		public function remove($value) {
			$values = preg_split('%\s+%', $value, 0, PREG_SPLIT_NO_EMPTY);
			
			foreach ($values as $value) {
				if (!in_array($value, $this->classes)) continue;
				
				$index = array_search($value, $this->classes);
				
				unset($this->classes[$index]);
			}
			
			$value = implode(' ', $this->classes);
			
			if ($value) {
				$this->element->setAttribute('class', $value);
			}
			
			else {
				$this->element->removeAttribute('class');
			}
			
			return $this;
		}
	}
	
/*----------------------------------------------------------------*/
	
	class HTMLBuilderElement extends HTMLBuilder {
		protected $document;
		protected $element;
		
		public function __construct(HTMLBuilder $builder) {
			$this->document = $builder->document();
			$this->element = $builder->element();
		}
	}
	
	class HTMLBuilderElementIterator extends HTMLBuilder implements Iterator, Countable {
		protected $builder;
		protected $document;
		protected $index;
		protected $list;
		
		public function __construct(HTMLBuilder $builder) {
			$this->builder = $builder;
			$this->document = $builder->document();
			$this->list = $builder->element()->childNodes;
		}
		
		public function __toString() {
			$value = '';
			
			foreach ($this->list as $node) {
				$value .= $this->document->saveXML($node);
			}
			
			return $value;
		}
		
		public function rewind() {
			$this->index = 0;
		}
		
		public function count() {
			return $this->list->length;
		}
		
		public function current() {
			$node = $this->list->item($this->index);
			$element = $this->builder->element();
			$this->builder->element($node);
			
			if ($node instanceof DOMElement) {
				$node = new HTMLBuilderElement($this->builder);
			}
			
			else if ($node instanceof DOMCharacterData) {
				$node = new HTMLBuilderText($this->builder);
			}
			
			$this->builder->element($element);
			
			return $node;
		}
		
		public function key() {
			return $this->index;
		}
		
		public function next() {
			$this->index++;
		}
		
		public function valid() {
			return $this->list->item($this->index);
		}
	}
	
/*----------------------------------------------------------------*/
	
	class HTMLBuilderText extends HTMLBuilder {
		protected $document;
		protected $element;
		
		public function __construct(HTMLBuilder $builder) {
			$this->document = $builder->document();
			$this->element = $builder->element();
		}
		
		public function __get($name) {
			switch ($name) {
				case 'value':
					return $this->element()->data;
					
				default:
					throw new Exception(sprintf(
						"Property '%s' does not exist.", $name
					));
			}
		}
		
		public function __set($name, $value) {
			switch ($name) {
				case 'value':
					$this->element()->data = $value;
					
					return $this;
					
				default:
					throw new Exception(sprintf(
						"Property '%s' does not exist.", $name
					));
			}
		}
	}
	
/*----------------------------------------------------------------*/
?>