<?php
/**
 * @package hasFiles
 * @author tehcvillage <support@techvill.org>
 * @contributor Millat <[millat.techvill@gmail.com]>
 * @created 18-09-2021
 */

namespace App\Traits\ModelTraits;

use App\Models\File;

trait hasFiles
{
	/**
	 * object type of file
	 * @return string
	 */
	protected function objectType()
	{
		return self::getTable();
	}

    /**
     * object id of file
     * @return string
     */
    protected function objectId()
    {
        return !is_null($this->id) ? $this->id : static::max('id');
    }

	/**
	 * upload folder of object file
	 * @return string
	 */
	protected function uploadPath()
	{
		return createDirectory(join(DIRECTORY_SEPARATOR, ['public', 'uploads', $this->objectType()]));
	}

	/**
	 * file path of a give file
	 * @param  string $fileName
	 * @return string
	 */
	protected function filePath($fileName)
	{
		return join(DIRECTORY_SEPARATOR, [$this->uploadPath(), $fileName]);
	}

    /**
     * default file
     * @param  string $fileName
     * @return string
     */
    protected function defaultFileUrl(string $type)
    {
        return url(defaultImage($type));
    }

	/**
	 * difine relationship of a model
	 * @return collection
	 */
    public function file()
    {
        return $this->hasOne('App\Models\File', 'object_id')->where('object_type', $this->objectType());
    }

    /**
     * upload file(s)
     * @param  array  $options
     * @return none
     */
	public function uploadFiles(array $options = [])
    {
        foreach (request()->all() as $key => $value) {
            if (request()->hasFile($key)) {
                (new File)->store([request()->$key], $this->uploadPath(), $this->objectType(), $this->objectId(), $options);
            }
        }
    }

    /**
     * upload file(s) from url
     * @param  array  $options
     * @return none
     */
    public function uploadFilesFromUrl($url, array $options = [])
    {
        (new File)->storeFromUrl($url, $this->uploadPath(), $this->objectType(), $this->objectId(), $options);
    }

    /**
     * upload file(s)
     * @param  array  $options
     * @return none
     */
    public function updateFiles(array $options = [])
    {
        foreach (request()->all() as $key => $value) {
            if (request()->hasFile($key)) {

                $this->deleteFiles();

                (new File)->store([request()->$key], $this->uploadPath(), $this->objectType(), $this->objectId(), $options);
            }
        }
    }

    /**
     * get file url from model instance
     * @param  array  $options
     * @return string
     */
    public function fileUrl(array $options = []): string
    {
        $options = array_merge([
            'default' => true,
            'type' => $this->objectType()
        ], $options);

        $file = $this->file()->first();

        if (is_null($file)) {
            return $this->defaultFileUrl($options['type']);
        }

        if (!file_exists($this->filePath($file->file_name))) {
            return $this->defaultFileUrl($options['type']);
        }

        return url($this->filePath($file->file_name));
    }

    /**
     * get files url
     * @param  array  $options
     * @return array
     */
    public function filesUrl(array $options = []): array
    {
    	$options = array_merge([
            'default' => true,
            'type' => $this->objectType()
        ], $options);

        $files = $this->file()->get();

        if ($files->count() <= 0 ) {
            return [$this->defaultFileUrl($options['type'])];
        }

        $filesUrl = [];

        foreach ($files as $key => $file) {

            if (!file_exists($this->filePath($file->file_name))) {
                $filesUrl[$key] = $this->defaultFileUrl($options['type']);
            } else {
                $filesUrl[$key] = url($this->filePath($file->file_name));
            }
        }

        return $filesUrl;
    }

    /**
     * delete of object file(s)
     * @return json
     */
    public function deleteFiles()
    {
        $fileIDs = File::where(['object_type' => $this->objectType(), 'object_id' => $this->objectId()])
                ->get()
                ->pluck('id')
                ->toArray();

        if (empty($fileIDs)) {
            return false;
        }

        return (new File)->deleteFiles(
                $this->objectType(),
                $this->objectId(),
                ['ids' => [$fileIDs], 'isExceptId' => false],
                $this->uploadPath()
            );
    }
}
