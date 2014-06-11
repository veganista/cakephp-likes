<?php

App::uses('AlreadyLikedException', 'Likes.Error');

class LikeableBehavior extends ModelBehavior {

	private $defaults = array(
		'userKey' => 'user_id',
		'foreignKey' => 'foreign_key',
		'modelKey' => 'model',
		'counterCacheKey' => 'like_count',
		'dependent' => true,
	);

	public function setup( Model $Model, $settings = array() ) {

		if( !isset( $this->settings[ $Model->alias ] ) ){
			$this->settings[ $Model->alias ] = $this->defaults;
		}

		$this->settings[ $Model->alias ] = Set::merge( $this->settings[ $Model->alias ], $settings );

		// bind the associations
		$Model->bindModel(array(
			'hasMany' => array(
				'Like' => array(
					'className' => 'Like',
					'foreignKey' => $this->settings[ $Model->alias ]['foreignKey'],
					'conditions' => array( 'Like.' . $this->settings[ $Model->alias ][ 'modelKey' ] => $Model->alias ),
					'dependent' => $this->settings[ $Model->alias ]['dependent'],
				),
			),
		), false);

		$Model->Like->bindModel(array(
			'belongsTo' => array(
				$Model->alias => array(
					'className' => $Model->alias,
					'foreignKey' => $this->settings[ $Model->alias ]['foreignKey'],
					'conditions' => array( 'Like.' . $this->settings[ $Model->alias ][ 'modelKey' ] => $Model->alias ),
					'counterCache' => $this->settings[ $Model->alias ]['counterCacheKey'],
					'counterScope' => array( 'Like.' . $this->settings[ $Model->alias ][ 'modelKey' ] => $Model->alias ),
				),
			),
		), false);

	}

	public function like( Model $Model, $foreignId = false, $userId = false ) {

		if( ! $Model->exists( $foreignId ) ){
			throw new NotFoundException();
		}

		if( $this->isLikedBy( $Model, $foreignId, $userId ) ) {
			throw new AlreadyLikedException();
		}

		$data = array( 'Like' => array(
			$this->settings[ $Model->alias ][ 'userKey' ] => $userId,
			$this->settings[ $Model->alias ][ 'foreignKey' ] => $foreignId,
			$this->settings[ $Model->alias ][ 'modelKey' ] => $Model->alias,
		));

		$Model->Like->create();

		return $Model->Like->save( $data );		

	}

	public function isLikedBy( Model $Model, $foreignId, $userId ) {
		
		if( ! $Model->exists( $foreignId ) ){
			throw new NotFoundException();
		}

		$count = $Model->Like->find( 'count', array(
			'conditions' => array(
				'Like.' . $this->settings[ $Model->alias ][ 'modelKey' ] => $Model->alias, 
				'Like.' . $this->settings[ $Model->alias ][ 'foreignKey' ] => $foreignId,
				'Like.' . $this->settings[ $Model->alias ][ 'userKey' ] => $userId,
			)
		));

		return $count > 0;

	}

}