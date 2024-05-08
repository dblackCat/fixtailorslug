<?php namespace CatDesign\FixTailorSlug\Classes\Event;

use App;
use October\Rain\Support\Str;
use Tailor\Models\EntryRecord;

/**
 * Class ExtendProductModel
 *
 * @author Semen Kuznetsov (dblackCat)
 * @link   https://cat-design.ru
 */
class EntryRecordModelHandler
{
    /**
     * Slug param
     *
     * @var string[]
     */
    protected $slugs = ['slug' => 'title'];


    /**
     * Active model
     *
     * @var EntryRecord
     */
    protected $activeModel = null;


    /**
     * Subscribe
     *
     * @return void
     */
    public function subscribe()
    {
        EntryRecord::extend(function (EntryRecord $model) {
            $model->bindEvent('model.saveInternal', function (array $modelData) use ($model) {
                $this->activeModel = $model;
                $this->slugAttributes();
            }, 500);
        });
    }


    /**
     * slugAttributes adds slug attributes to the dataset, used before saving.
     * @return void
     */
    public function slugAttributes()
    {
        foreach ($this->slugs as $slugAttribute => $sourceAttributes) {
            $this->setSluggedValue($slugAttribute, $sourceAttributes);
        }
    }


    /**
     * setSluggedValue sets a single slug attribute value, using source attributes
     * to generate the slug from and a maximum length for the slug not including
     * the counter. Source attributes support dotted notation for relations.
     *
     * @param string $slugAttribute
     * @param mixed $sourceAttributes
     * @param int $maxLength
     * @return string
     */
    public function setSluggedValue($slugAttribute, $sourceAttributes, $maxLength = 175)
    {
        if (!array_key_exists($slugAttribute, $this->activeModel->attributes) || !mb_strlen($this->activeModel->attributes[$slugAttribute])) {
            if (!is_array($sourceAttributes)) {
                $sourceAttributes = [$sourceAttributes];
            }

            $slugArr = [];
            foreach ($sourceAttributes as $attribute) {
                $slugArr[] = $this->getSluggableSourceAttributeValue($attribute);
            }

            $slug = implode(' ', $slugArr);
            $slug = mb_substr($slug, 0, $maxLength);
            $slug = Str::slug($slug, $this->getSluggableSeparator(), App::getLocale());
        }
        else {
            $slug = $this->activeModel->attributes[$slugAttribute] ?? '';
        }

        // Source attributes contain empty values, nothing to slug and this
        // happens when the attributes are not required by the validator
        if (!mb_strlen(trim($slug))) {
            return $this->activeModel->attributes[$slugAttribute] = '';
        }

        return $this->activeModel->attributes[$slugAttribute] = $this->getSluggableUniqueAttributeValue($slugAttribute, $slug);
    }


    /**
     * getSluggableUniqueAttributeValue ensures a unique attribute value, if the value is already
     * used a counter suffix is added. Returns a safe value that is unique.
     *
     * @param string $name
     * @param mixed $value
     * @return string
     */
    protected function getSluggableUniqueAttributeValue($name, $value)
    {
        $counter = 1;
        $separator = $this->getSluggableSeparator();
        $_value = $value;

        while ($this->newSluggableQuery()->where($name, $_value)->count() > 0) {
            $counter++;
            $_value = $value . $separator . $counter;
        }

        return $_value;
    }


    /**
     * getSluggableSourceAttributeValue using dotted notation.
     *
     * Eg: author.name
     * @return mixed
     */
    protected function getSluggableSourceAttributeValue($key)
    {
        if (strpos($key, '.') === false) {
            return $this->activeModel->getAttribute($key);
        }

        $keyParts = explode('.', $key);
        $value = $this;
        foreach ($keyParts as $part) {
            if (!isset($value[$part])) {
                return null;
            }

            $value = $value[$part];
        }

        return $value;
    }


    /**
     * getSluggableSeparator is an override for the default slug separator.
     *
     * @return string
     */
    public function getSluggableSeparator()
    {
        return '-';
    }


    /**
     * newSluggableQuery returns a query that excludes the current record if it exists
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function newSluggableQuery()
    {
        $query = $this->activeModel->newQuery();

        if ($this->activeModel->exists) {
            $query->where($this->activeModel->getKeyName(), '<>', $this->activeModel->getKey());
        }

        if ($this->activeModel->isClassInstanceOf(\October\Contracts\Database\SoftDeleteInterface::class)) {
            $this->activeModel->withTrashed();
        }

        if (
            $this->activeModel->isClassInstanceOf(\October\Contracts\Database\MultisiteInterface::class) &&
            $this->activeModel->isMultisiteEnabled()
        ) {
            $query->withSite($this->activeModel->{$this->activeModel->getSiteIdColumn()});
        }

        return $query;
    }
}
