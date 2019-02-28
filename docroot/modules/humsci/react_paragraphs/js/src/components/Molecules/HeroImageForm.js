import React from "react";
import {MediaField} from "../Atoms/MediaField";

export const HeroImageForm = ({entity}) => {
  let heroImageValue = '';
  if (typeof (entity.field_hs_hero_image) !== 'undefined' && entity.field_hs_hero_image.length) {
    heroImageValue = entity.field_hs_hero_image[0].target_id
  }
  return (
    <div>
      <div className="form-item">
        <label>Image</label>
        <MediaField
          data={heroImageValue}/>
      </div>
    </div>
  )
};
