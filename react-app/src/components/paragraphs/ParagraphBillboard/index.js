import React from 'react';
import './ParagraphBillboard.css';

const ParagraphBillboard = ({ title, description }) => {
  return (
    <div className="billboard">
      <h2>{title}</h2>
      <div dangerouslySetInnerHTML={{ __html: description }} />
    </div>
  );
};

export default ParagraphBillboard; 