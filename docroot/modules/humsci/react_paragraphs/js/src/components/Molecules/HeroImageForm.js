import React from "react";
import {MediaField} from "../Atoms/MediaField";

export const HeroImageForm = ({item, onFieldEdit}) => {
  let heroImageValue = '';
  if (typeof (item.entity.field_hs_hero_image) !== 'undefined' && item.entity.field_hs_hero_image.length) {
    heroImageValue = item.entity.field_hs_hero_image[0].target_id
  }
  return (
    <div>

      <MediaField
        label="Image"
        value={heroImageValue}
        allowedTypes={['image']}
        name="field_hs_hero_image[0][target_id]"
        onChange={onFieldEdit}
      />

    </div>
  )
};
